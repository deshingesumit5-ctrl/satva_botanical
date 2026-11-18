<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BatchActivityExecution;
use App\Models\BatchHold;
use App\Models\BatchMaterialAllocation;
use App\Models\BatchMaterialConsumption;
use App\Models\BatchMaterialRequirement;
use App\Models\BatchParameterReading;
use App\Models\BatchReconciliation;
use App\Models\BatchStageExecution;
use App\Models\BatchStageOutput;
use App\Models\QcRequest;
use App\Models\QcRequestResult;
use App\Models\RecoveryLot;
use App\Models\ReworkRecord;
use App\Models\StockLot;
use App\Models\WasteRecord;
use App\Models\WipRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionService
{
    protected DocumentNumberService $docService;
    protected StockService $stockService;

    public function __construct(DocumentNumberService $docService, StockService $stockService)
    {
        $this->docService = $docService;
        $this->stockService = $stockService;
    }

    /**
     * Calculate planned material requirements based on recipe quantity rules and batch planned size.
     */
    public function generateMaterialRequirements(Batch $batch): void
    {
        $batch->loadMissing('recipeVersion.stages.inputMaterials');
        $version = $batch->recipeVersion;
        if (!$version) {
            return;
        }
        $refBatchSize = (float) ($version->reference_batch_size ?: $batch->planned_qty);
        $ratio = ($refBatchSize > 0) ? ((float) $batch->planned_qty / $refBatchSize) : 1.0;

        foreach ($version->stages as $stage) {
            foreach ($stage->inputMaterials as $material) {
                if ($material->quantity_basis === 'Percentage') {
                    $qty = ((float) $batch->planned_qty * (float) $material->percentage_basis) / 100.0;
                } elseif ($material->quantity_basis === 'Fixed') {
                    $qty = (float) $material->standard_qty;
                } else {
                    $qty = (float) $material->standard_qty * $ratio;
                }

                $plannedQty = round($qty, 4);
                $existingAllocated = (float) BatchMaterialAllocation::where('batch_id', $batch->id)
                    ->where('recipe_stage_id', $stage->id)
                    ->where('material_id', $material->material_id)
                    ->sum('allocated_qty');

                $status = 'Pending';
                if ($existingAllocated >= $plannedQty && $plannedQty > 0) {
                    $status = 'Fully Allocated';
                } elseif ($existingAllocated > 0) {
                    $status = 'Partially Allocated';
                }

                $req = BatchMaterialRequirement::firstOrCreate(
                    [
                        'batch_id'        => $batch->id,
                        'recipe_stage_id' => $stage->id,
                        'material_id'     => $material->material_id,
                    ],
                    [
                        'planned_qty'   => $plannedQty,
                        'allocated_qty' => $existingAllocated,
                        'uom_id'        => $material->uom_id,
                        'status'        => $status,
                    ]
                );

                if ((float) $req->allocated_qty <= 0 && $existingAllocated > 0) {
                    $req->allocated_qty = $existingAllocated;
                    $req->status = $status;
                    $req->save();
                }
            }
        }
    }

    /**
     * Readiness validation before starting a batch (Validation 7).
     */
    public function validateBatchReadiness(Batch $batch): array
    {
        $errors = [];

        if ($batch->recipe->status !== 'Active' && $batch->recipe->status !== 'Approved') {
            $errors[] = 'Cannot start production: The recipe is not approved or active.';
        }

        if ($batch->recipeVersion->status !== 'Active' && $batch->recipeVersion->status !== 'Approved') {
            $errors[] = 'Cannot start production: The recipe version is not approved or active.';
        }

        if ((float) $batch->planned_qty <= 0) {
            $errors[] = 'Planned batch quantity must be greater than zero.';
        }

        return $errors;
    }

    /**
     * Start batch execution: loads stages, creates stage executions for all stages, marks first stage In Progress.
     */
    public function startBatch(Batch $batch, int $userId): void
    {
        $errors = $this->validateBatchReadiness($batch);
        if (!empty($errors)) {
            throw new RuntimeException(implode(' ', $errors));
        }

        DB::transaction(function () use ($batch, $userId) {
            $batch->status = 'In Progress';
            $batch->actual_start_time = now();
            $batch->save();

            if ($batch->stageExecutions()->count() === 0) {
                $stages = $batch->recipeVersion->stages()->orderBy('sequence_no')->get();
                $first = true;
                foreach ($stages as $stage) {
                    $exec = BatchStageExecution::create([
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

                    foreach ($stage->activities as $act) {
                        if ($act->activity) {
                            BatchActivityExecution::create([
                                'batch_stage_execution_id' => $exec->id,
                                'recipe_stage_activity_id' => $act->id,
                                'activity_name'            => $act->activity->name,
                                'sequence_no'              => $act->sequence_no,
                                'status'                   => 'Pending',
                            ]);
                        }
                    }

                    foreach ($stage->parameters as $param) {
                        if ($param->parameter) {
                            BatchParameterReading::create([
                                'batch_stage_execution_id' => $exec->id,
                                'recipe_parameter_id'      => $param->id,
                                'parameter_name'           => $param->parameter->name,
                                'parameter_type'           => $param->parameter->parameter_type ?? 'Numeric',
                                'uom'                      => $param->parameter->uom,
                                'target_value'             => $param->target_value,
                                'min_value'                => $param->min_value,
                                'max_value'                => $param->max_value,
                                'is_within_range'          => true,
                            ]);
                        }
                    }

                    $first = false;
                }
            }

            AuditService::log('Production', 'Start Batch', "Batch: {$batch->batch_number}", null, [
                'batch_id' => $batch->id,
                'status'   => 'In Progress',
            ], 'Started batch production');
        });
    }

    /**
     * Evaluate all applicable completion conditions for a stage execution (Document Section 7.2).
     */
    public function evaluateStageCompletion(BatchStageExecution $execution): array
    {
        return app(StageRequirementEvaluator::class)->evaluate($execution);
    }

    /**
     * Check if a stage execution is Locked because any preceding stage is not yet satisfied.
     */
    public function getStageLockState(Batch $batch, BatchStageExecution $targetExecution): array
    {
        if ((int) $targetExecution->sequence_no <= 1) {
            return [
                'locked'               => false,
                'blocking_sequence_no' => null,
                'blocking_stage_name'  => null,
                'tooltip'              => null,
            ];
        }

        $allExecs = $batch->stageExecutions()
            ->with(['recipeStage.activities', 'recipeStage.parameters', 'recipeStage.inputMaterials', 'recipeStage.outputMaterials', 'recipeStage.qcTests', 'activityExecutions', 'parameterReadings.recipeParameter', 'outputs', 'qcRequest'])
            ->orderBy('sequence_no')
            ->orderBy('execution_number')
            ->get();

        $precedingBySeq = $allExecs
            ->where('sequence_no', '<', $targetExecution->sequence_no)
            ->groupBy('sequence_no');

        foreach ($precedingBySeq as $seqNo => $seqExecs) {
            /** @var BatchStageExecution $latestExec */
            $latestExec = $seqExecs->last();
            $eval = $this->evaluateStageCompletion($latestExec);

            if (!$eval['can_unlock_next_stage']) {
                return [
                    'locked'               => true,
                    'blocking_sequence_no' => $seqNo,
                    'blocking_stage_name'  => $latestExec->stage_name,
                    'tooltip'              => "Complete Seq {$seqNo} first",
                ];
            }
        }

        return [
            'locked'               => false,
            'blocking_sequence_no' => null,
            'blocking_stage_name'  => null,
            'tooltip'              => null,
        ];
    }

    /**
     * Add a new repetition execution record for a stage (never overwrites previous executions).
     */
    public function addStageRepetition(BatchStageExecution $currentExecution, int $userId): BatchStageExecution
    {
        return DB::transaction(function () use ($currentExecution, $userId) {
            $batch = $currentExecution->batch;
            $recipeStage = $currentExecution->recipeStage;

            // Mark current repetition as Completed if it was In Progress
            if ($currentExecution->status === 'In Progress') {
                $endTime = now();
                $currentExecution->end_time = $endTime;
                if ($currentExecution->start_time) {
                    $currentExecution->duration_minutes = Carbon::parse($currentExecution->start_time)->diffInMinutes($endTime);
                }
                $currentExecution->completed_at = $endTime;
                $currentExecution->status = 'Completed';
                $currentExecution->save();
            }

            $maxExecNum = BatchStageExecution::where('batch_id', $batch->id)
                ->where('recipe_stage_id', $currentExecution->recipe_stage_id)
                ->max('execution_number') ?? 1;

            $newExecNum = $maxExecNum + 1;
            $baseStageName = preg_replace('/\s*\((Repetition|Rework)\s*#\d+\)$/i', '', $currentExecution->stage_name);

            $newExec = BatchStageExecution::create([
                'batch_id'             => $batch->id,
                'recipe_stage_id'      => $currentExecution->recipe_stage_id,
                'execution_number'     => $newExecNum,
                'stage_name'           => "{$baseStageName} (Repetition #{$newExecNum})",
                'sequence_no'          => $currentExecution->sequence_no,
                'occurrence_no'        => $currentExecution->occurrence_no,
                'execution_type'       => 'Repeat',
                'status'               => 'In Progress',
                'qc_status'            => $recipeStage && $recipeStage->qc_required ? 'Pending' : 'Not Required',
                'equipment_id'         => $currentExecution->equipment_id,
                'actual_operator_name' => $currentExecution->actual_operator_name,
                'operator_count'       => $currentExecution->operator_count,
                'start_time'           => now(),
            ]);

            if ($recipeStage) {
                foreach ($recipeStage->activities as $act) {
                    if ($act->activity) {
                        BatchActivityExecution::create([
                            'batch_stage_execution_id' => $newExec->id,
                            'recipe_stage_activity_id' => $act->id,
                            'activity_name'            => $act->activity->name,
                            'sequence_no'              => $act->sequence_no,
                            'status'                   => 'Pending',
                        ]);
                    }
                }

                foreach ($recipeStage->parameters as $param) {
                    if ($param->parameter) {
                        BatchParameterReading::create([
                            'batch_stage_execution_id' => $newExec->id,
                            'recipe_parameter_id'      => $param->id,
                            'parameter_name'           => $param->parameter->name,
                            'parameter_type'           => $param->parameter->parameter_type ?? 'Numeric',
                            'uom'                      => $param->parameter->uom,
                            'target_value'             => $param->target_value,
                            'min_value'                => $param->min_value,
                            'max_value'                => $param->max_value,
                            'is_within_range'          => true,
                        ]);
                    }
                }
            }

            AuditService::log('Production', 'Add Stage Repetition', "Batch: {$batch->batch_number}, Stage: {$newExec->stage_name}", null, [
                'previous_execution_id' => $currentExecution->id,
                'new_execution_id'      => $newExec->id,
                'execution_number'      => $newExecNum,
                'user_id'               => $userId,
            ], "Added repetition #{$newExecNum} for stage {$baseStageName}");

            return $newExec;
        });
    }

    /**
     * Authorize a stage execution by a supervisor with 'Authorize Stage' permission.
     */
    public function authorizeStage(BatchStageExecution $execution, int $userId, ?string $remarks = null): void
    {
        $execution->supervisor_authorized_by = $userId;
        $execution->supervisor_authorized_at = now();
        $execution->supervisor_auth_remarks = $remarks ?? 'Authorized by Supervisor';
        $execution->save();

        AuditService::log('Production', 'Authorize Stage', "Batch: {$execution->batch->batch_number}, Stage: {$execution->stage_name}", null, [
            'execution_id'  => $execution->id,
            'authorized_by' => $userId,
            'authorized_at' => $execution->supervisor_authorized_at->toDateTimeString(),
            'remarks'       => $execution->supervisor_auth_remarks,
        ], 'Supervisor manually authorized stage completion');
    }

    /**
     * Unlock the next stage in sequence after current stage is fully satisfied.
     */
    public function unlockNextStageIfEligible(BatchStageExecution $completedExecution): ?BatchStageExecution
    {
        $eval = $this->evaluateStageCompletion($completedExecution);
        if (!$eval['can_unlock_next_stage']) {
            return null;
        }

        $nextExec = BatchStageExecution::where('batch_id', $completedExecution->batch_id)
            ->where('sequence_no', '>', $completedExecution->sequence_no)
            ->whereIn('status', ['Pending', 'Ready'])
            ->orderBy('sequence_no')
            ->orderBy('execution_number')
            ->first();

        $batch = $completedExecution->batch;

        if ($nextExec) {
            $nextExec->status = 'In Progress';
            if (!$nextExec->start_time) {
                $nextExec->start_time = now();
            }
            $nextExec->save();

            AuditService::log('Production', 'Unlock Stage', "Batch: {$batch->batch_number}, Stage: {$nextExec->stage_name}", null, [
                'unlocked_execution_id' => $nextExec->id,
                'sequence_no'           => $nextExec->sequence_no,
                'unlocked_after_stage'  => $completedExecution->stage_name,
            ], "Unlocked next stage Seq {$nextExec->sequence_no}: {$nextExec->stage_name}");

            NotificationService::notifyRole(
                'production_supervisor',
                'Production Stage Pending',
                "Batch {$batch->batch_number} - Stage Seq {$nextExec->sequence_no} ({$nextExec->stage_name}) is now unlocked and ready for execution.",
                route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $nextExec->id]),
                'production_stage_pending'
            );

            return $nextExec;
        }

        // Last stage completed: batch remains 'In Progress' and becomes ready for Reconciliation (never auto-closed)
        if ($batch->status === 'QC Pending') {
            $batch->status = 'In Progress';
            $batch->save();
        }

        NotificationService::notifyRole(
            'production_supervisor',
            'Production Stage Pending',
            "All stages completed for Batch {$batch->batch_number}. Batch is ready for Mass Balance Reconciliation.",
            route('production.reconciliation.show', $batch->id),
            'production_stage_pending'
        );

        return null;
    }

    /**
     * Hold batch with reason.
     */
    public function holdBatch(Batch $batch, string $reason, int $userId): BatchHold
    {
        return DB::transaction(function () use ($batch, $reason, $userId) {
            $batch->status = 'On Hold';
            $batch->save();

            $hold = BatchHold::create([
                'batch_id'    => $batch->id,
                'hold_reason' => $reason,
                'held_at'     => now(),
                'held_by'     => $userId,
            ]);

            AuditService::log('Production', 'Hold Batch', "Batch: {$batch->batch_number}", null, [
                'reason'  => $reason,
                'held_at' => now(),
            ], $reason);

            return $hold;
        });
    }

    /**
     * Resume batch. Calculates hold duration.
     */
    public function resumeBatch(Batch $batch, string $resumeReason, int $userId): void
    {
        DB::transaction(function () use ($batch, $resumeReason, $userId) {
            $hold = BatchHold::where('batch_id', $batch->id)->whereNull('resumed_at')->latest()->first();
            if ($hold) {
                $now = now();
                $duration = Carbon::parse($hold->held_at)->diffInMinutes($now);
                $hold->resume_reason = $resumeReason;
                $hold->resumed_at = $now;
                $hold->resumed_by = $userId;
                $hold->duration_minutes = $duration;
                $hold->save();
            }

            $batch->status = 'In Progress';
            $batch->save();

            AuditService::log('Production', 'Resume Batch', "Batch: {$batch->batch_number}", null, [
                'resume_reason' => $resumeReason,
                'resumed_at'    => now(),
            ], $resumeReason);
        });
    }

    /**
     * Complete a stage execution (verifying all Section 7.2 conditions on the server).
     */
    public function completeStage(BatchStageExecution $execution, array $outputsData = [], ?string $supervisorRemarks = null): void
    {
        DB::transaction(function () use ($execution, $outputsData, $supervisorRemarks) {
            // Check that the stage itself is not locked
            $lockState = $this->getStageLockState($execution->batch, $execution);
            if ($lockState['locked']) {
                throw new RuntimeException("Stage Locked: {$lockState['tooltip']}");
            }

            // Save outputs if provided before evaluating completion requirements
            foreach ($outputsData as $out) {
                if (!empty($out['actual_qty']) && (float) $out['actual_qty'] > 0) {
                    BatchStageOutput::create([
                        'batch_stage_execution_id' => $execution->id,
                        'output_type'              => $out['output_type'],
                        'material_id'              => $out['material_id'] ?? null,
                        'product_id'               => $out['product_id'] ?? null,
                        'output_name'              => $out['output_name'],
                        'actual_qty'               => $out['actual_qty'],
                        'uom_id'                   => $out['uom_id'],
                        'lot_or_wip_no'            => $out['lot_or_wip_no'] ?? null,
                        'destination_warehouse_id' => $out['destination_warehouse_id'] ?? null,
                        'qc_required'              => !empty($out['qc_required']),
                        'remarks'                  => $out['remarks'] ?? null,
                    ]);

                    if ($out['output_type'] === 'WIP') {
                        WipRecord::create([
                            'wip_number'          => $this->docService->generate('WIP Number'),
                            'batch_id'            => $execution->batch_id,
                            'source_stage_id'     => $execution->recipe_stage_id,
                            'source_execution_id' => $execution->id,
                            'material_id'         => $out['material_id'] ?? null,
                            'product_id'          => $out['product_id'] ?? null,
                            'material_name'       => $out['output_name'],
                            'generated_qty'       => $out['actual_qty'],
                            'consumed_qty'        => 0,
                            'available_qty'       => $out['actual_qty'],
                            'uom_id'              => $out['uom_id'],
                            'qc_status'           => 'Approved',
                            'warehouse_id'        => $out['destination_warehouse_id'] ?? $execution->batch->productionPlan?->warehouse_id,
                            'status'              => 'Available',
                        ]);
                    }

                    if ($out['output_type'] === 'Recovered' && !empty($out['material_id'])) {
                        RecoveryLot::create([
                            'recovery_lot_number' => $this->docService->generate('Recovery Lot Number'),
                            'source_batch_id'     => $execution->batch_id,
                            'source_stage_id'     => $execution->recipe_stage_id,
                            'material_id'         => $out['material_id'],
                            'generated_qty'       => $out['actual_qty'],
                            'available_qty'       => $out['actual_qty'],
                            'reserved_qty'        => 0,
                            'consumed_qty'        => 0,
                            'sold_qty'            => 0,
                            'remaining_qty'       => $out['actual_qty'],
                            'warehouse_id'        => $out['destination_warehouse_id'] ?? $execution->batch->productionPlan?->warehouse_id,
                            'recovery_date'       => now()->toDateString(),
                            'qc_status'           => 'Approved',
                            'status'              => 'Active',
                        ]);
                    }

                    if (in_array($out['output_type'], ['Spent', 'Waste'])) {
                        WasteRecord::create([
                            'batch_id'      => $execution->batch_id,
                            'stage_id'      => $execution->recipe_stage_id,
                            'waste_type'    => $out['output_type'] === 'Spent' ? 'Spent Material' : 'Waste Material',
                            'item_name'     => $out['output_name'],
                            'quantity'      => $out['actual_qty'],
                            'uom_id'        => $out['uom_id'],
                            'warehouse_id'  => $out['destination_warehouse_id'] ?? null,
                            'disposal_date' => now()->toDateString(),
                            'created_by'    => Auth::id(),
                        ]);
                    }
                }
            }

            // Re-evaluate all completion conditions on the server
            $execution->unsetRelation('outputs');
            $eval = $this->evaluateStageCompletion($execution);

            if (!empty($eval['operational_unmet'])) {
                throw new RuntimeException('Stage completion requirements not met: ' . implode(' | ', $eval['operational_unmet']));
            }

            if ($execution->status === 'QC Pending' && $execution->qc_status !== 'Approved') {
                throw new RuntimeException('Stage completion requirements not met: QC approval: pending');
            }

            $endTime = now();
            $execution->end_time = $endTime;
            if ($execution->start_time) {
                $execution->duration_minutes = Carbon::parse($execution->start_time)->diffInMinutes($endTime);
            }
            $execution->completed_at = $endTime;
            $execution->supervisor_remarks = $supervisorRemarks ?? $execution->supervisor_remarks;

            // Check if QC approval is required and not yet approved
            if ($eval['qc_required'] && $execution->qc_status !== 'Approved') {
                $execution->status = 'QC Pending';
                $execution->qc_status = 'Pending';
                $execution->save();

                $qcReq = app(QcService::class)->createForStageExecution(
                    $execution,
                    Auth::id() ?? $execution->batch->supervisor_id ?? 1
                );

                $batch = $execution->batch;
                $batch->status = 'QC Pending';
                $batch->save();

                AuditService::log('Production', 'Submit Stage for QC', "Batch: {$execution->batch->batch_number}, Stage: {$execution->stage_name}", null, [
                    'execution_id'      => $execution->id,
                    'qc_request_id'     => $qcReq->id,
                    'qc_request_number' => $qcReq->qc_request_number,
                    'status'            => 'QC Pending',
                ], 'Stage submitted to QC; next stage remains locked until QC approval');

                return;
            }

            $execution->status = 'Completed';
            $execution->save();

            AuditService::log('Production', 'Complete Stage', "Batch: {$execution->batch->batch_number}, Stage: {$execution->stage_name}", null, [
                'execution_id'     => $execution->id,
                'status'           => $execution->status,
                'duration_minutes' => $execution->duration_minutes,
            ], 'Completed stage execution');

            // Unlock next stage only when all requirements are met
            $this->unlockNextStageIfEligible($execution);
        });
    }

    /**
     * Batch closure & reconciliation validation (Validations 21 & 22).
     */
    public function validateBatchClosure(Batch $batch): array
    {
        $errors = [];

        $pendingMandatory = $batch->stageExecutions()
            ->whereHas('recipeStage', fn($q) => $q->where('is_mandatory', true))
            ->whereNotIn('status', ['Completed', 'Approved'])
            ->get();

        if ($pendingMandatory->count() > 0) {
            $names = $pendingMandatory->pluck('stage_name')->implode(', ');
            $errors[] = "Validation Error (21): Cannot close batch while mandatory stages are incomplete: {$names}";
        }

        $pendingQc = $batch->qcRequests()->where('status', 'Pending')->get();
        if ($pendingQc->count() > 0) {
            $errors[] = "Validation Error (22): Cannot close batch with pending QC approvals.";
        }

        return $errors;
    }

    /**
     * Reconcile and close the batch.
     */
    public function closeBatch(Batch $batch, array $reconciliationData, int $userId): BatchReconciliation
    {
        $errors = $this->validateBatchClosure($batch);
        if (!empty($errors)) {
            throw new RuntimeException(implode(' ', $errors));
        }

        return DB::transaction(function () use ($batch, $reconciliationData, $userId) {
            $totalInput = (float) ($reconciliationData['raw_material_qty'] ?? 0)
                + (float) ($reconciliationData['water_qty'] ?? 0)
                + (float) ($reconciliationData['chemical_qty'] ?? 0)
                + (float) ($reconciliationData['solvent_qty'] ?? 0)
                + (float) ($reconciliationData['other_input_qty'] ?? 0);

            $totalOutput = (float) ($reconciliationData['fg_qty'] ?? 0)
                + (float) ($reconciliationData['wip_qty'] ?? 0)
                + (float) ($reconciliationData['cake_qty'] ?? 0)
                + (float) ($reconciliationData['liquid_qty'] ?? 0)
                + (float) ($reconciliationData['recovered_solvent_qty'] ?? 0)
                + (float) ($reconciliationData['by_product_qty'] ?? 0)
                + (float) ($reconciliationData['spent_qty'] ?? 0)
                + (float) ($reconciliationData['waste_qty'] ?? 0);

            $returns = (float) ($reconciliationData['material_returns_qty'] ?? 0);
            $difference = $totalInput - ($totalOutput + $returns);

            $recon = BatchReconciliation::updateOrCreate(
                ['batch_id' => $batch->id],
                [
                    'total_input_qty'            => $totalInput,
                    'raw_material_qty'           => $reconciliationData['raw_material_qty'] ?? 0,
                    'water_qty'                  => $reconciliationData['water_qty'] ?? 0,
                    'chemical_qty'               => $reconciliationData['chemical_qty'] ?? 0,
                    'solvent_qty'                => $reconciliationData['solvent_qty'] ?? 0,
                    'other_input_qty'            => $reconciliationData['other_input_qty'] ?? 0,
                    'total_output_qty'           => $totalOutput,
                    'fg_qty'                     => $reconciliationData['fg_qty'] ?? 0,
                    'wip_qty'                    => $reconciliationData['wip_qty'] ?? 0,
                    'cake_qty'                   => $reconciliationData['cake_qty'] ?? 0,
                    'liquid_qty'                 => $reconciliationData['liquid_qty'] ?? 0,
                    'recovered_solvent_qty'      => $reconciliationData['recovered_solvent_qty'] ?? 0,
                    'by_product_qty'             => $reconciliationData['by_product_qty'] ?? 0,
                    'spent_qty'                  => $reconciliationData['spent_qty'] ?? 0,
                    'waste_qty'                  => $reconciliationData['waste_qty'] ?? 0,
                    'material_returns_qty'       => $returns,
                    'unaccounted_difference_qty' => $difference,
                    'reconciled_at'              => now(),
                    'reconciled_by'              => $userId,
                    'remarks'                    => $reconciliationData['remarks'] ?? null,
                ]
            );

            $batch->actual_qty = (float) ($reconciliationData['fg_qty'] ?? 0);
            $batch->actual_end_time = now();
            $batch->status = 'Completed';
            $batch->save();

            AuditService::log('Production', 'Close Batch', "Batch: {$batch->batch_number}", null, [
                'actual_qty'     => $batch->actual_qty,
                'difference_qty' => $difference,
                'status'         => 'Completed',
            ], 'Reconciled and closed batch');

            return $recon;
        });
    }
}
