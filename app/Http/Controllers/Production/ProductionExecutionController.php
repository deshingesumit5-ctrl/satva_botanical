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
use App\Services\ProductionService;
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

    public function show(Batch $batch, Request $request)
    {
        $batch->load([
            'product',
            'recipe',
            'recipeVersion',
            'stageExecutions.stage',
            'stageExecutions.equipment',
            'stageExecutions.activities.recipeStageActivity.activity',
            'stageExecutions.parameters.recipeParameter.parameter',
            'stageExecutions.outputs.material',
            'stageExecutions.outputs.product',
            'stageExecutions.outputs.uom',
            'materialAllocations.stockLot.material',
            'materialConsumptions.stockLot',
        ]);

        $stageExecutions = $batch->stageExecutions()->orderBy('sequence_no')->orderBy('execution_number')->get();

        // Find active stage execution or requested execution
        $activeExecutionId = $request->input('stage_execution_id');
        $activeExecution = null;

        if ($activeExecutionId) {
            $activeExecution = $stageExecutions->firstWhere('id', $activeExecutionId);
        }

        if (!$activeExecution) {
            $activeExecution = $stageExecutions->firstWhere('status', 'In Progress')
                ?? $stageExecutions->firstWhere('status', 'QC Pending')
                ?? $stageExecutions->firstWhere('status', 'Pending')
                ?? $stageExecutions->last();
        }

        // Sync missing stage activities, parameter readings, and material charges from RecipeStage
        if ($activeExecution && $activeExecution->recipeStage) {
            $recipeStage = $activeExecution->recipeStage()->with([
                'activities.activity',
                'parameters.parameter',
                'inputMaterials.material',
                'outputMaterials',
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
                                'sequence_no' => $act->sequence_no,
                                'status' => 'Pending',
                            ]
                        );
                    }
                }

                foreach ($recipeStage->parameters as $param) {
                    if ($param->parameter) {
                        BatchParameterReading::firstOrCreate(
                            [
                                'batch_stage_execution_id' => $activeExecution->id,
                                'recipe_parameter_id' => $param->id,
                            ],
                            [
                                'parameter_name' => $param->parameter->name,
                                'parameter_type' => $param->parameter->parameter_type ?? 'Numeric',
                                'uom' => $param->parameter->uom,
                                'target_value' => $param->target_value,
                                'min_value' => $param->min_value,
                                'max_value' => $param->max_value,
                                'is_within_range' => true,
                            ]
                        );
                    }
                }

                $refBatchSize = (float) ($batch->recipeVersion->reference_batch_size ?? $batch->planned_qty ?: 1);
                $ratio = $refBatchSize > 0 ? ((float) $batch->planned_qty / $refBatchSize) : 1.0;

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
                                    'batch_id' => $batch->id,
                                    'recipe_stage_id' => $recipeStage->id,
                                    'material_id' => $rm->material_id,
                                    'stock_lot_id' => $lotId,
                                    'allocated_qty' => $stdQty,
                                    'actual_consumed_qty' => $stdQty,
                                    'returned_qty' => 0,
                                    'net_consumed_qty' => $stdQty,
                                    'uom_id' => $rm->uom_id ?? $batch->uom_id,
                                    'entered_by' => Auth::id() ?? 1,
                                ]);
                            }
                        }
                    }
                }

                $activeExecution->load([
                    'parameters.recipeParameter.parameter',
                    'outputs',
                    'recipeStage.outputMaterials',
                ]);
            }
        }

        $equipments = Equipment::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->orderBy('name')->get();
        $products = Product::where('status', 'Active')->orderBy('name')->get();

        return view('production.execution.show', compact(
            'batch',
            'stageExecutions',
            'activeExecution',
            'equipments',
            'warehouses',
            'uoms',
            'materials',
            'products'
        ));
    }

    public function startStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $stageExecution->update([
            'status' => 'In Progress',
            'start_time' => now(),
        ]);
        return back()->with('success', "Stage '{$stageExecution->stage_name}' started.");
    }

    protected function persistStageData(Request $request, Batch $batch, BatchStageExecution $stageExecution): void
    {
        // 1. Update Equipment & Clearance (Section A)
        if ($request->has('equipment_id') && $request->input('equipment_id')) {
            $stageExecution->equipment_id = $request->input('equipment_id');
        }
        if ($request->has('line_clearance_by') || $request->has('actual_operator_name')) {
            $stageExecution->actual_operator_name = $request->input('line_clearance_by') ?? $request->input('actual_operator_name') ?? $stageExecution->actual_operator_name;
        }
        if ($request->has('operator_count')) {
            $stageExecution->operator_count = $request->input('operator_count', $stageExecution->operator_count);
        }
        $stageExecution->save();

        // 2. Update Activities (timings)
        $activities = $request->input('activities', []);
        foreach ($activities as $actId => $data) {
            $act = BatchActivityExecution::find($actId);
            if ($act && $act->batch_stage_execution_id === $stageExecution->id) {
                $act->start_datetime = !empty($data['start_datetime']) ? $data['start_datetime'] : null;
                $act->end_datetime = !empty($data['end_datetime']) ? $data['end_datetime'] : null;
                $act->operator_name = $data['operator_name'] ?? null;
                $act->remarks = $data['remarks'] ?? null;
                if ($act->start_datetime && $act->end_datetime) {
                    $act->status = 'Completed';
                }
                $act->save();
            }
        }

        // 3. Update Parameters / Readings (Section B)
        $parameters = $request->input('parameters', []);
        foreach ($parameters as $key => $data) {
            $readingId = $data['id'] ?? $key;
            $reading = BatchParameterReading::find($readingId);
            if ($reading && $reading->batch_stage_execution_id === $stageExecution->id) {
                $valInput = $data['actual_value'] ?? $data['logged_value'] ?? null;
                $reading->actual_value = ($valInput !== '' && $valInput !== null) ? $valInput : null;
                $reading->entered_at = now();
                $reading->entered_by = Auth::id();

                if ($reading->actual_value !== null && is_numeric($reading->actual_value)) {
                    $val = (float) $reading->actual_value;
                    $min = ($reading->min_value !== null && $reading->min_value !== '') ? (float) $reading->min_value : null;
                    $max = ($reading->max_value !== null && $reading->max_value !== '') ? (float) $reading->max_value : null;

                    $within = true;
                    if ($min !== null && $val < $min) {
                        $within = false;
                    }
                    if ($max !== null && $val > $max) {
                        $within = false;
                    }
                    $reading->is_within_range = $within;
                }
                $reading->save();
            }
        }

        // 4. Update Material Charges & Consumptions (Section C)
        $materialsInput = $request->input('materials', []);
        foreach ($materialsInput as $key => $mData) {
            $consId = $mData['id'] ?? $key;
            $cons = BatchMaterialConsumption::find($consId);
            if ($cons && $cons->batch_id === $batch->id) {
                $actualQty = isset($mData['actual_quantity']) ? (float) $mData['actual_quantity'] : (float) $cons->actual_consumed_qty;
                $cons->actual_consumed_qty = $actualQty;
                $cons->net_consumed_qty = max(0, $actualQty - (float) $cons->returned_qty);
                $cons->entered_by = Auth::id() ?? $cons->entered_by;
                $cons->save();
            }
        }

        // 5. Update Intermediate Yield, Solvent Recovery & Spent Herb (Section D)
        $defaultUomId = $batch->uom_id ?? Uom::value('id');
        $ltrUomId = Uom::where('uom_code', 'LTR')->value('id') ?? $defaultUomId;
        $kgUomId = Uom::where('uom_code', 'KG')->value('id') ?? $defaultUomId;

        if ($request->has('output_quantity')) {
            $outQty = (float) $request->input('output_quantity', 0);
            $maxSeq = (int) $batch->stageExecutions()->max('sequence_no');
            $outType = ($stageExecution->sequence_no >= $maxSeq) ? 'Finished' : 'WIP';
            if ($outQty > 0) {
                BatchStageOutput::updateOrCreate(
                    [
                        'batch_stage_execution_id' => $stageExecution->id,
                        'output_type' => $outType,
                    ],
                    [
                        'product_id' => $outType === 'Finished' ? $batch->product_id : null,
                        'output_name' => $outType === 'Finished' ? ($batch->product->name ?? 'Finished Extract') : ($stageExecution->stage_name . ' Intermediate'),
                        'actual_qty' => $outQty,
                        'uom_id' => $defaultUomId,
                    ]
                );
            }
        }

        if ($request->has('solvent_recovered_qty')) {
            $solvQty = (float) $request->input('solvent_recovered_qty', 0);
            if ($solvQty > 0) {
                $solventMatId = Material::where('material_category', 'Solvent')->value('id');
                BatchStageOutput::updateOrCreate(
                    [
                        'batch_stage_execution_id' => $stageExecution->id,
                        'output_type' => 'Recovered',
                    ],
                    [
                        'material_id' => $solventMatId,
                        'output_name' => 'Recovered Solvent',
                        'actual_qty' => $solvQty,
                        'uom_id' => $ltrUomId,
                    ]
                );
            }
        }

        if ($request->has('spent_herb_qty')) {
            $spentQty = (float) $request->input('spent_herb_qty', 0);
            if ($spentQty > 0) {
                BatchStageOutput::updateOrCreate(
                    [
                        'batch_stage_execution_id' => $stageExecution->id,
                        'output_type' => 'Spent',
                    ],
                    [
                        'output_name' => 'Spent Herb / Solid Waste',
                        'actual_qty' => $spentQty,
                        'uom_id' => $kgUomId,
                    ]
                );
            }
        }
    }

    public function saveStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $this->persistStageData($request, $batch, $stageExecution);

        AuditService::log('production', $batch->batch_no ?? $batch->batch_number, 'save_stage_execution', null, null, "Saved progress for stage {$stageExecution->stage_name}");

        return redirect()->route('production.batches.execute', ['batch' => $batch->id, 'stage_execution_id' => $stageExecution->id])
            ->with('success', 'Stage progress saved.');
    }

    public function completeStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $this->persistStageData($request, $batch, $stageExecution);

        $outputs = $request->input('outputs', []);
        $remarks = $request->input('supervisor_remarks');

        try {
            $this->productionService->completeStage($stageExecution, $outputs, $remarks);

            return redirect()->route('production.batches.execute', ['batch' => $batch->id])
                ->with('success', "Stage '{$stageExecution->stage_name}' completed successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
