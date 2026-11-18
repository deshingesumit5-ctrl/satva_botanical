<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchActivityExecution;
use App\Models\BatchMaterialConsumption;
use App\Models\BatchParameterReading;
use App\Models\BatchStageExecution;
use App\Models\BatchStageOutput;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Product;
use App\Models\StockLot;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\ProductionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionExecutionController extends Controller
{
    protected ProductionService $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function show(Batch $batch, Request $request, $stage_execution_id = null)
    {
        // Auto-initialize stage executions if batch has none yet
        if ($batch->stageExecutions()->count() === 0 && $batch->recipeVersion) {
            $stages = $batch->recipeVersion->stages()->orderBy('sequence_no')->get();
            $first = true;
            foreach ($stages as $stage) {
                BatchStageExecution::create([
                    'batch_id'             => $batch->id,
                    'recipe_stage_id'      => $stage->id,
                    'execution_number'     => 1,
                    'stage_name'           => $stage->stage->stage_name ?? $stage->stage->name ?? ('Stage ' . $stage->sequence_no),
                    'sequence_no'          => $stage->sequence_no,
                    'occurrence_no'        => $stage->occurrence_no,
                    'execution_type'       => 'Original',
                    'status'               => $first ? 'In Progress' : 'Pending',
                    'qc_status'            => $stage->qc_required ? 'Pending' : 'Not Required',
                    'equipment_id'         => $stage->stage?->default_equipment_id,
                    'actual_operator_name' => $batch->supervisor_name,
                    'operator_count'       => $batch->operator_count,
                    'start_time'           => $first ? now() : null,
                ]);
                $first = false;
            }
        }

        $batch->load([
            'product',
            'recipe',
            'recipeVersion',
            'stageExecutions.stage',
            'stageExecutions.recipeStage',
            'stageExecutions.equipment',
            'stageExecutions.activities.recipeStageActivity.activity',
            'stageExecutions.parameters.recipeParameter.parameter',
            'stageExecutions.outputs.material',
            'stageExecutions.outputs.product',
            'stageExecutions.outputs.uom',
            'stageExecutions.authorizedByUser',
            'stageExecutions.qcRequest',
            'materialAllocations.stockLot.material',
            'materialConsumptions.stockLot',
        ]);

        $stageExecutions = $batch->stageExecutions()
            ->with(['stage', 'recipeStage', 'authorizedByUser', 'qcRequest'])
            ->orderBy('sequence_no')
            ->orderBy('execution_number')
            ->get();

        // Compute lock state for every stage execution
        $stageLockStates = [];
        foreach ($stageExecutions as $se) {
            $stageLockStates[$se->id] = $this->productionService->getStageLockState($batch, $se);
        }

        $requestedStageId = $stage_execution_id ?? $request->input('stage_execution_id');
        $activeExecution = null;

        if ($requestedStageId) {
            $targetExec = $stageExecutions->firstWhere('id', (int) $requestedStageId);
            if ($targetExec) {
                $lockInfo = $stageLockStates[$targetExec->id] ?? ['locked' => false];
                if (!empty($lockInfo['locked'])) {
                    $msg = "Stage Locked: {$lockInfo['tooltip']}. Stage Seq {$targetExec->sequence_no} ({$targetExec->stage_name}) cannot be opened until preceding stage completion requirements are met.";
                    if ($request->expectsJson()) {
                        return response()->json(['message' => $msg], 403);
                    }
                    return response()->view('errors.403', [
                        'message'        => $msg,
                        'permissionCode' => $lockInfo['tooltip'],
                    ], 403);
                }
                $activeExecution = $targetExec;
            }
        }

        if (!$activeExecution) {
            $unlockedExecs = $stageExecutions->filter(fn($se) => empty($stageLockStates[$se->id]['locked']));
            $activeExecution = $unlockedExecs->firstWhere('status', 'In Progress')
                ?? $unlockedExecs->firstWhere('status', 'Rework Required')
                ?? $unlockedExecs->firstWhere('status', 'QC Pending')
                ?? $unlockedExecs->firstWhere('status', 'Ready')
                ?? $unlockedExecs->firstWhere('status', 'Pending')
                ?? $unlockedExecs->last()
                ?? $stageExecutions->first();
        }

        // Sync missing stage activities, parameter readings, and material charges from RecipeStage only for editable stages
        if ($activeExecution && $activeExecution->recipeStage && !in_array($activeExecution->status, ['Completed', 'Approved', 'Skipped', 'QC Pending'], true)) {
            $recipeStage = $activeExecution->recipeStage()->with([
                'activities.activity',
                'parameters.parameter',
                'inputMaterials.material',
                'outputMaterials',
                'qcTests',
            ])->first();

            if ($recipeStage) {
                foreach ($recipeStage->activities as $act) {
                    if ($act->activity) {
                        BatchActivityExecution::firstOrCreate(
                            [
                                'batch_stage_execution_id' => $activeExecution->id,
                                'recipe_stage_activity_id' => $act->id,
                            ],
                            [
                                'activity_name' => $act->activity->name,
                                'sequence_no'   => $act->sequence_no,
                                'status'        => 'Pending',
                            ]
                        );
                    }
                }

                foreach ($recipeStage->parameters as $param) {
                    if ($param->parameter) {
                        BatchParameterReading::firstOrCreate(
                            [
                                'batch_stage_execution_id' => $activeExecution->id,
                                'recipe_parameter_id'      => $param->id,
                            ],
                            [
                                'parameter_name'  => $param->parameter->name,
                                'parameter_type'  => $param->parameter->parameter_type ?? 'Numeric',
                                'uom'             => $param->parameter->uom,
                                'target_value'    => $param->target_value,
                                'min_value'       => $param->min_value,
                                'max_value'       => $param->max_value,
                                'is_within_range' => true,
                            ]
                        );
                    }
                }

                foreach ($recipeStage->inputMaterials as $rm) {
                    if ($rm->material_id) {
                        $existingCons = BatchMaterialConsumption::where('batch_id', $batch->id)
                            ->where('recipe_stage_id', $recipeStage->id)
                            ->where('material_id', $rm->material_id)
                            ->first();

                        if (!$existingCons) {
                            $alloc = $batch->materialAllocations()
                                ->where('material_id', $rm->material_id)
                                ->first();
                            $lotId = $alloc ? $alloc->stock_lot_id : StockLot::where('material_id', $rm->material_id)->value('id');

                            if ($lotId) {
                                $stdQty = (float) $rm->standard_qty;
                                BatchMaterialConsumption::create([
                                    'batch_id'            => $batch->id,
                                    'recipe_stage_id'     => $recipeStage->id,
                                    'material_id'         => $rm->material_id,
                                    'stock_lot_id'        => $lotId,
                                    'allocated_qty'       => $stdQty,
                                    'actual_consumed_qty' => $stdQty,
                                    'returned_qty'        => 0,
                                    'net_consumed_qty'    => $stdQty,
                                    'uom_id'              => $rm->uom_id ?? $batch->uom_id,
                                    'entered_by'          => Auth::id() ?? 1,
                                ]);
                            }
                        }
                    }
                }

                $activeExecution->load([
                    'activities.recipeStageActivity.activity',
                    'parameters.recipeParameter.parameter',
                    'outputs',
                    'recipeStage.outputMaterials',
                    'authorizedByUser',
                    'qcRequest.submittedByUser',
                ]);
            }
        }

        $completionEvaluation = $activeExecution
            ? $this->productionService->evaluateStageCompletion($activeExecution)
            : null;

        // Recompute lock states after syncing activeExecution relations
        foreach ($stageExecutions as $se) {
            $stageLockStates[$se->id] = $this->productionService->getStageLockState($batch, $se);
        }

        $equipments = Equipment::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->orderBy('name')->get();
        $products = Product::where('status', 'Active')->orderBy('name')->get();

        return response()
            ->view('production.execution.show', compact(
                'batch',
                'stageExecutions',
                'activeExecution',
                'stageLockStates',
                'completionEvaluation',
                'equipments',
                'warehouses',
                'uoms',
                'materials',
                'products'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function startStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $lock = $this->productionService->getStageLockState($batch, $stageExecution);
        if ($lock['locked']) {
            return response()->view('errors.403', [
                'message'        => "Stage Locked: {$lock['tooltip']}",
                'permissionCode' => $lock['tooltip'],
            ], 403);
        }

        $stageExecution->update([
            'status'     => 'In Progress',
            'start_time' => $stageExecution->start_time ?? now(),
        ]);

        return back()->with('success', "Stage '{$stageExecution->stage_name}' started.");
    }

    protected function persistStageData(Request $request, Batch $batch, BatchStageExecution $stageExecution): void
    {
        // 1. Update Equipment, Hold Time & Clearance (Section A)
        if ($request->has('equipment_id')) {
            $stageExecution->equipment_id = $request->input('equipment_id') ?: null;
        }
        if ($request->has('line_clearance_by') || $request->has('actual_operator_name')) {
            $stageExecution->actual_operator_name = $request->input('line_clearance_by') ?? $request->input('actual_operator_name') ?? $stageExecution->actual_operator_name;
        }
        if ($request->has('operator_count')) {
            $stageExecution->operator_count = $request->input('operator_count', $stageExecution->operator_count);
        }
        if ($request->has('actual_hold_time_minutes')) {
            $holdVal = $request->input('actual_hold_time_minutes');
            $stageExecution->actual_hold_time_minutes = ($holdVal !== null && $holdVal !== '') ? (int) $holdVal : null;
        }
        if ($request->has('supervisor_remarks')) {
            $stageExecution->supervisor_remarks = $request->input('supervisor_remarks');
        }
        $stageExecution->save();

        // 2. Update Activities (timings)
        $activities = $request->input('activities', []);
        foreach ($activities as $actId => $data) {
            $act = BatchActivityExecution::find($data['id'] ?? $actId);
            if ($act && $act->batch_stage_execution_id === $stageExecution->id) {
                $startDt = !empty($data['start_datetime']) ? $data['start_datetime'] : null;
                $endDt = !empty($data['end_datetime']) ? $data['end_datetime'] : null;
                $act->start_datetime = $startDt;
                $act->end_datetime = $endDt;
                if (array_key_exists('operator_name', $data)) {
                    $act->operator_name = $data['operator_name'];
                }
                if (array_key_exists('remarks', $data)) {
                    $act->remarks = $data['remarks'];
                }

                if ($startDt && $endDt) {
                    $act->status = 'Completed';
                    $act->actual_duration_minutes = max(0, Carbon::parse($startDt)->diffInMinutes(Carbon::parse($endDt), false));
                } elseif ($startDt) {
                    $act->status = 'In Progress';
                    $act->actual_duration_minutes = 0;
                } else {
                    $act->status = 'Pending';
                    $act->actual_duration_minutes = 0;
                }
                $act->save();
            }
        }

        // 3. Update Parameters / Readings (Section C) — never auto-correct out-of-range readings!
        $parameters = $request->input('parameters', []);
        foreach ($parameters as $key => $data) {
            $readingId = $data['id'] ?? $key;
            $reading = BatchParameterReading::find($readingId);
            if ($reading && $reading->batch_stage_execution_id === $stageExecution->id) {
                $valInput = $data['actual_value'] ?? $data['logged_value'] ?? null;
                $reading->actual_value = ($valInput !== '' && $valInput !== null) ? (string) $valInput : null;
                $reading->entered_at = now();
                $reading->entered_by = Auth::id();
                if (isset($data['remarks'])) {
                    $reading->remarks = $data['remarks'];
                }

                if ($reading->actual_value !== null && is_numeric($reading->actual_value)) {
                    $val = (float) $reading->actual_value;
                    $min = ($reading->min_value !== null && $reading->min_value !== '' && is_numeric($reading->min_value)) ? (float) $reading->min_value : null;
                    $max = ($reading->max_value !== null && $reading->max_value !== '' && is_numeric($reading->max_value)) ? (float) $reading->max_value : null;

                    $within = true;
                    if ($min !== null && $val < $min) {
                        $within = false;
                    }
                    if ($max !== null && $val > $max) {
                        $within = false;
                    }
                    $reading->is_within_range = $within;
                } elseif ($reading->actual_value === null) {
                    $reading->is_within_range = true;
                }
                $reading->save();
            }
        }

        // 4. Update Material Charges & Consumptions (Section D)
        $materialsInput = $request->input('materials', []);
        foreach ($materialsInput as $key => $mData) {
            $consId = $mData['id'] ?? $key;
            $cons = BatchMaterialConsumption::find($consId);
            if ($cons && $cons->batch_id === $batch->id) {
                $actualQty = isset($mData['actual_quantity']) && $mData['actual_quantity'] !== ''
                    ? (float) $mData['actual_quantity']
                    : 0.0;
                $cons->actual_consumed_qty = $actualQty;
                $cons->net_consumed_qty = max(0, $actualQty - (float) $cons->returned_qty);
                $cons->entered_by = Auth::id() ?? $cons->entered_by;
                $cons->save();
            }
        }

        // 5. Update Intermediate Yield, Solvent Recovery & Spent Herb (Section E)
        $defaultUomId = $batch->uom_id ?? Uom::value('id');
        $ltrUomId = Uom::where('uom_code', 'LTR')->value('id') ?? $defaultUomId;
        $kgUomId = Uom::where('uom_code', 'KG')->value('id') ?? $defaultUomId;

        if ($request->has('output_quantity')) {
            $outQty = (float) $request->input('output_quantity', 0);
            $maxSeq = (int) $batch->stageExecutions()->max('sequence_no');
            $outType = ($stageExecution->sequence_no >= $maxSeq) ? 'Finished' : 'WIP';
            $existingOut = BatchStageOutput::where('batch_stage_execution_id', $stageExecution->id)
                ->where('output_type', $outType)
                ->first();
            if ($outQty > 0) {
                BatchStageOutput::updateOrCreate(
                    [
                        'batch_stage_execution_id' => $stageExecution->id,
                        'output_type'              => $outType,
                    ],
                    [
                        'product_id'  => $outType === 'Finished' ? $batch->product_id : null,
                        'output_name' => $outType === 'Finished' ? ($batch->product->name ?? 'Finished Extract') : ($stageExecution->stage_name . ' Intermediate'),
                        'actual_qty'  => $outQty,
                        'uom_id'      => $defaultUomId,
                    ]
                );
            } elseif ($existingOut) {
                $existingOut->actual_qty = 0;
                $existingOut->save();
            }
        }

        if ($request->has('solvent_recovered_qty')) {
            $solvQty = (float) $request->input('solvent_recovered_qty', 0);
            $existingSolv = BatchStageOutput::where('batch_stage_execution_id', $stageExecution->id)
                ->where('output_type', 'Recovered')
                ->first();
            if ($solvQty > 0) {
                $solventMatId = Material::where('material_category', 'Solvent')->value('id');
                BatchStageOutput::updateOrCreate(
                    [
                        'batch_stage_execution_id' => $stageExecution->id,
                        'output_type'              => 'Recovered',
                    ],
                    [
                        'material_id' => $solventMatId,
                        'output_name' => 'Recovered Solvent',
                        'actual_qty'  => $solvQty,
                        'uom_id'      => $ltrUomId,
                    ]
                );
            } elseif ($existingSolv) {
                $existingSolv->actual_qty = 0;
                $existingSolv->save();
            }
        }

        if ($request->has('spent_herb_qty')) {
            $spentQty = (float) $request->input('spent_herb_qty', 0);
            $existingSpent = BatchStageOutput::where('batch_stage_execution_id', $stageExecution->id)
                ->where('output_type', 'Spent')
                ->first();
            if ($spentQty > 0) {
                BatchStageOutput::updateOrCreate(
                    [
                        'batch_stage_execution_id' => $stageExecution->id,
                        'output_type'              => 'Spent',
                    ],
                    [
                        'output_name' => 'Spent Herb / Solid Waste',
                        'actual_qty'  => $spentQty,
                        'uom_id'      => $kgUomId,
                    ]
                );
            } elseif ($existingSpent) {
                $existingSpent->actual_qty = 0;
                $existingSpent->save();
            }
        }
    }

    protected function buildExecutionFeedbackPayload(Batch $batch, BatchStageExecution $stageExecution, array $evaluation): array
    {
        $stageExecution->loadMissing([
            'recipeStage',
            'authorizedByUser',
            'qcRequest.submittedByUser',
            'activityExecutions',
            'parameterReadings',
        ]);

        $stageHistory = $batch->stageExecutions()
            ->where('recipe_stage_id', $stageExecution->recipe_stage_id)
            ->orderBy('execution_number')
            ->get();

        $checklistHtml = view('production.execution._checklist', [
            'batch'                => $batch,
            'activeExecution'      => $stageExecution,
            'completionEvaluation' => $evaluation,
            'stageHistory'         => $stageHistory,
        ])->render();

        $parameterStatuses = [];
        foreach ($stageExecution->parameterReadings as $r) {
            $parameterStatuses[$r->id] = [
                'actual_value'    => $r->actual_value,
                'is_within_range' => (bool) $r->is_within_range,
            ];
        }

        $activityStatuses = [];
        foreach ($stageExecution->activityExecutions as $a) {
            $activityStatuses[$a->id] = $a->status;
        }

        return [
            'evaluation'         => $evaluation,
            'checklist_html'     => $checklistHtml,
            'parameter_statuses' => $parameterStatuses,
            'activity_statuses'  => $activityStatuses,
        ];
    }

    public function saveStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $lock = $this->productionService->getStageLockState($batch, $stageExecution);
        if ($lock['locked']) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => "Stage Locked: {$lock['tooltip']}"], 403);
            }
            return response()->view('errors.403', [
                'message'        => "Stage Locked: {$lock['tooltip']}",
                'permissionCode' => $lock['tooltip'],
            ], 403);
        }

        if (in_array($stageExecution->status, ['QC Pending', 'Completed', 'Approved', 'Skipped'], true)) {
            $msg = "Stage '{$stageExecution->stage_name}' is {$stageExecution->status} and cannot be edited.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        DB::transaction(function () use ($request, $batch, $stageExecution) {
            $this->persistStageData($request, $batch, $stageExecution);
        });

        AuditService::log('production', $batch->batch_no ?? $batch->batch_number, 'save_stage_execution', null, null, "Saved progress for stage {$stageExecution->stage_name}");

        if ($request->expectsJson()) {
            $freshExec = $stageExecution->fresh([
                'recipeStage.activities.activity',
                'recipeStage.parameters.parameter',
                'recipeStage.inputMaterials.material',
                'recipeStage.outputMaterials',
                'recipeStage.qcTests',
                'activityExecutions.recipeStageActivity',
                'parameterReadings.recipeParameter.parameter',
                'outputs',
                'qcRequest.submittedByUser',
                'authorizedByUser',
            ]);
            $evaluation = $this->productionService->evaluateStageCompletion($freshExec);
            $payload = $this->buildExecutionFeedbackPayload($batch, $freshExec, $evaluation);

            return response()->json(array_merge([
                'success'  => true,
                'saved'    => true,
                'saved_at' => now()->format('H:i'),
                'message'  => 'Stage progress saved.',
            ], $payload));
        }

        return redirect()->route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $stageExecution->id])
            ->with('success', 'Stage progress saved.');
    }

    public function completeStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $lock = $this->productionService->getStageLockState($batch, $stageExecution);
        if ($lock['locked']) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => "Stage Locked: {$lock['tooltip']}"], 403);
            }
            return response()->view('errors.403', [
                'message'        => "Stage Locked: {$lock['tooltip']}",
                'permissionCode' => $lock['tooltip'],
            ], 403);
        }

        if (in_array($stageExecution->status, ['QC Pending', 'Completed', 'Approved', 'Skipped'], true)) {
            $msg = "Stage '{$stageExecution->stage_name}' is already {$stageExecution->status}.";
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'completed' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $outputs = $request->input('outputs', []);
        $remarks = $request->input('supervisor_remarks');

        try {
            $txResult = DB::transaction(function () use ($request, $batch, $stageExecution, $outputs, $remarks) {
                $lockedExec = BatchStageExecution::where('id', $stageExecution->id)->lockForUpdate()->firstOrFail();

                if (in_array($lockedExec->status, ['QC Pending', 'Completed', 'Approved', 'Skipped'], true)) {
                    return [
                        'already_processed' => true,
                        'completed'         => false,
                        'status'            => $lockedExec->status,
                    ];
                }

                // Step 1: Save all on-screen data first
                $this->persistStageData($request, $batch, $lockedExec);

                // Step 2: Evaluate all completion requirements
                $freshExec = $lockedExec->fresh([
                    'recipeStage.activities.activity',
                    'recipeStage.parameters.parameter',
                    'recipeStage.inputMaterials.material',
                    'recipeStage.outputMaterials',
                    'recipeStage.qcTests',
                    'activityExecutions.recipeStageActivity',
                    'parameterReadings.recipeParameter.parameter',
                    'outputs',
                    'qcRequest.submittedByUser',
                    'authorizedByUser',
                ]);
                $evaluation = $this->productionService->evaluateStageCompletion($freshExec);

                if (!empty($evaluation['operational_unmet']) && empty($outputs)) {
                    // Commit the saved screen data, do NOT complete the stage
                    return [
                        'completed'  => false,
                        'evaluation' => $evaluation,
                        'execution'  => $freshExec,
                    ];
                }

                // Step 3: Complete stage (or move to QC Pending) in the same transaction
                $this->productionService->completeStage($freshExec, $outputs, $remarks);
                $afterExec = $freshExec->fresh();

                return [
                    'completed'  => true,
                    'execution'  => $afterExec,
                    'evaluation' => $this->productionService->evaluateStageCompletion($afterExec),
                ];
            });

            if (!empty($txResult['already_processed'])) {
                $msg = "Stage '{$stageExecution->stage_name}' is already {$txResult['status']}.";
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'completed' => false, 'message' => $msg], 409);
                }
                return back()->with('warning', $msg);
            }

            if (!$txResult['completed']) {
                $evaluation = $txResult['evaluation'];
                $unmetCount = count($evaluation['operational_unmet']);
                $msg = "Cannot complete yet: {$unmetCount} requirement(s) not met";

                if ($request->expectsJson()) {
                    $payload = $this->buildExecutionFeedbackPayload($batch, $txResult['execution'], $evaluation);
                    return response()->json(array_merge([
                        'success'     => false,
                        'saved'       => true,
                        'saved_at'    => now()->format('H:i'),
                        'completed'   => false,
                        'unmet_count' => $unmetCount,
                        'message'     => $msg,
                    ], $payload), 422);
                }

                return back()->withInput()->with('error', $msg);
            }

            $afterExec = $txResult['execution'];
            if ($afterExec->status === 'QC Pending') {
                $msg = "Stage '{$afterExec->stage_name}' submitted to QC Pending. Next stage remains locked until QC Approves.";
                $redirectUrl = route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $afterExec->id]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success'      => true,
                        'saved'        => true,
                        'completed'    => true,
                        'qc_pending'   => true,
                        'message'      => $msg,
                        'redirect_url' => $redirectUrl,
                    ]);
                }

                return redirect($redirectUrl)->with('warning', $msg);
            }

            $msg = "Stage '{$afterExec->stage_name}' completed and next stage unlocked.";
            $redirectUrl = route('production.execution.show', ['batch' => $batch->id]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success'      => true,
                    'saved'        => true,
                    'completed'    => true,
                    'qc_pending'   => false,
                    'message'      => $msg,
                    'redirect_url' => $redirectUrl,
                ]);
            }

            return redirect($redirectUrl)->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                $freshExec = $stageExecution->fresh();
                $evaluation = $this->productionService->evaluateStageCompletion($freshExec);
                $unmetCount = count($evaluation['operational_unmet'] ?? []);
                $msg = $unmetCount > 0 ? "Cannot complete yet: {$unmetCount} requirement(s) not met" : $e->getMessage();
                $payload = $this->buildExecutionFeedbackPayload($batch, $freshExec, $evaluation);
                return response()->json(array_merge([
                    'success'     => false,
                    'saved'       => true,
                    'saved_at'    => now()->format('H:i'),
                    'completed'   => false,
                    'unmet_count' => $unmetCount,
                    'message'     => $msg,
                ], $payload), 422);
            }
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function addRepetition(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $lock = $this->productionService->getStageLockState($batch, $stageExecution);
        if ($lock['locked']) {
            return response()->view('errors.403', [
                'message'        => "Stage Locked: {$lock['tooltip']}",
                'permissionCode' => $lock['tooltip'],
            ], 403);
        }

        $this->persistStageData($request, $batch, $stageExecution);
        $newExec = $this->productionService->addStageRepetition($stageExecution, Auth::id() ?? 1);

        return redirect()->route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $newExec->id])
            ->with('success', "Repetition #{$newExec->execution_number} created for {$stageExecution->stage_name}. Original execution preserved.");
    }

    public function authorizeStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !$user->hasPermission('Authorize Stage')) {
            return response()->view('errors.403', [
                'message'        => 'Operators without the Authorize Stage permission cannot self-authorize stage completion.',
                'permissionCode' => 'production.authorize_stage.approve',
            ], 403);
        }

        $lock = $this->productionService->getStageLockState($batch, $stageExecution);
        if ($lock['locked']) {
            return response()->view('errors.403', [
                'message'        => "Stage Locked: {$lock['tooltip']}",
                'permissionCode' => $lock['tooltip'],
            ], 403);
        }

        $this->productionService->authorizeStage($stageExecution, $user->id, $request->input('remarks'));

        return redirect()->route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $stageExecution->id])
            ->with('success', "Stage '{$stageExecution->stage_name}' authorized by {$user->name}.");
    }

    public function skipStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $lock = $this->productionService->getStageLockState($batch, $stageExecution);
        if ($lock['locked']) {
            return response()->view('errors.403', [
                'message'        => "Stage Locked: {$lock['tooltip']}",
                'permissionCode' => $lock['tooltip'],
            ], 403);
        }

        $recipeStage = $stageExecution->recipeStage;
        if (!$recipeStage || (!$recipeStage->is_optional && !$recipeStage->is_conditional)) {
            return back()->with('error', 'Only optional or conditional stages can be skipped.');
        }

        $stageExecution->status = 'Skipped';
        $stageExecution->completed_at = now();
        $stageExecution->supervisor_remarks = $request->input('reason', 'Optional/Conditional stage skipped');
        $stageExecution->save();

        AuditService::log('Production', 'Skip Stage', "Batch: {$batch->batch_number}, Stage: {$stageExecution->stage_name}", null, [
            'execution_id' => $stageExecution->id,
            'status'       => 'Skipped',
        ], $stageExecution->supervisor_remarks);

        $this->productionService->unlockNextStageIfEligible($stageExecution);

        return redirect()->route('production.execution.show', ['batch' => $batch->id])
            ->with('success', "Optional/Conditional Stage '{$stageExecution->stage_name}' marked as Skipped.");
    }
}
