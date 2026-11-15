<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BatchHold;
use App\Models\BatchMaterialRequirement;
use App\Models\BatchMaterialAllocation;
use App\Models\BatchMaterialConsumption;
use App\Models\BatchStageExecution;
use App\Models\BatchActivityExecution;
use App\Models\BatchParameterReading;
use App\Models\BatchStageOutput;
use App\Models\BatchReconciliation;
use App\Models\QcRequest;
use App\Models\QcRequestResult;
use App\Models\StockLot;
use App\Models\WipRecord;
use App\Models\RecoveryLot;
use App\Models\WasteRecord;
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
        $version = $batch->recipeVersion;
        $refBatchSize = (float)($version->reference_batch_size ?: $batch->planned_qty);
        $ratio = ($refBatchSize > 0) ? ((float)$batch->planned_qty / $refBatchSize) : 1.0;

        foreach ($version->stages as $stage) {
            foreach ($stage->inputMaterials as $material) {
                if ($material->quantity_basis === 'Percentage') {
                    $qty = ((float)$batch->planned_qty * (float)$material->percentage_basis) / 100.0;
                } elseif ($material->quantity_basis === 'Fixed') {
                    $qty = (float)$material->standard_qty;
                } else {
                    // Reference Ratio
                    $qty = (float)$material->standard_qty * $ratio;
                }

                BatchMaterialRequirement::updateOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'recipe_stage_id' => $stage->id,
                        'material_id' => $material->material_id,
                    ],
                    [
                        'planned_qty' => round($qty, 4),
                        'uom_id' => $material->uom_id,
                        'status' => 'Pending',
                    ]
                );
            }
        }
    }

    /**
     * Readiness validation before starting a batch (Validation 7).
     */
    public function validateBatchReadiness(Batch $batch): array
    {
        $errors = [];

        // Validation 7: Unapproved recipe
        if ($batch->recipe->status !== 'Active' && $batch->recipe->status !== 'Approved') {
            $errors[] = 'Cannot start production: The recipe is not approved or active.';
        }

        if ($batch->recipeVersion->status !== 'Active' && $batch->recipeVersion->status !== 'Approved') {
            $errors[] = 'Cannot start production: The recipe version is not approved or active.';
        }

        if ((float)$batch->planned_qty <= 0) {
            $errors[] = 'Planned batch quantity must be greater than zero.';
        }

        return $errors;
    }

    /**
     * Start batch execution: loads stages, creates stage executions for all stages, marks first stage Ready/In Progress.
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

            // Populate stage executions from recipe version if not already created
            if ($batch->stageExecutions()->count() === 0) {
                $stages = $batch->recipeVersion->stages()->orderBy('sequence_no')->get();
                $first = true;
                foreach ($stages as $stage) {
                    $exec = BatchStageExecution::create([
                        'batch_id' => $batch->id,
                        'recipe_stage_id' => $stage->id,
                        'execution_number' => 1,
                        'stage_name' => $stage->stage->stage_name,
                        'sequence_no' => $stage->sequence_no,
                        'occurrence_no' => $stage->occurrence_no,
                        'execution_type' => 'Original',
                        'status' => $first ? 'In Progress' : 'Pending',
                        'qc_status' => $stage->qc_required ? 'Pending' : 'Not Required',
                        'equipment_id' => $stage->stage->default_equipment_id,
                        'actual_operator_name' => $batch->supervisor_name,
                        'operator_count' => $batch->operator_count,
                        'start_time' => $first ? now() : null,
                    ]);

                    // Initialize activity executions
                    foreach ($stage->activities as $act) {
                        BatchActivityExecution::create([
                            'batch_stage_execution_id' => $exec->id,
                            'recipe_stage_activity_id' => $act->id,
                            'activity_name' => $act->activity->name,
                            'sequence_no' => $act->sequence_no,
                            'status' => 'Pending',
                        ]);
                    }

                    // Initialize parameter readings
                    foreach ($stage->parameters as $param) {
                        BatchParameterReading::create([
                            'batch_stage_execution_id' => $exec->id,
                            'recipe_parameter_id' => $param->id,
                            'parameter_name' => $param->parameter->name,
                            'parameter_type' => $param->parameter->parameter_type,
                            'uom' => $param->parameter->uom,
                            'target_value' => $param->target_value,
                            'min_value' => $param->min_value,
                            'max_value' => $param->max_value,
                            'is_within_range' => true,
                        ]);
                    }

                    $first = false;
                }
            }

            AuditService::log('Production', 'Start Batch', "Batch: {$batch->batch_number}", null, [
                'batch_id' => $batch->id,
                'status' => 'In Progress',
            ], 'Started batch production');
        });
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
                'batch_id' => $batch->id,
                'hold_reason' => $reason,
                'held_at' => now(),
                'held_by' => $userId,
            ]);

            AuditService::log('Production', 'Hold Batch', "Batch: {$batch->batch_number}", null, [
                'reason' => $reason,
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
                'resumed_at' => now(),
            ], $resumeReason);
        });
    }

    /**
     * Complete a stage execution (Sections A-G checks).
     * Validates activity timings (Validation 13) and mandatory readings (Validation 12).
     */
    public function completeStage(BatchStageExecution $execution, array $outputsData = [], ?string $supervisorRemarks = null): void
    {
        DB::transaction(function () use ($execution, $outputsData, $supervisorRemarks) {
            // Validation 13: Missing mandatory activity timing -> Activity cannot complete
            $pendingActivities = $execution->activityExecutions()
                ->whereHas('recipeStageActivity', fn($q) => $q->where('is_mandatory', true))
                ->where(function ($q) {
                    $q->whereNull('start_datetime')->orWhereNull('end_datetime');
                })
                ->get();

            if ($pendingActivities->count() > 0) {
                $names = $pendingActivities->pluck('activity_name')->implode(', ');
                throw new RuntimeException("Validation Error (13): Mandatory activities require start and end time before completion: {$names}");
            }

            // Validation 12: Missing mandatory actual reading -> Stage cannot complete
            $missingParams = $execution->parameterReadings()
                ->whereHas('recipeParameter', fn($q) => $q->where('is_mandatory', true))
                ->whereNull('actual_value')
                ->get();

            if ($missingParams->count() > 0) {
                $names = $missingParams->pluck('parameter_name')->implode(', ');
                throw new RuntimeException("Validation Error (12): Mandatory parameter readings must be entered before stage completion: {$names}");
            }

            // Save outputs if provided
            foreach ($outputsData as $out) {
                if (!empty($out['actual_qty']) && (float)$out['actual_qty'] > 0) {
                    BatchStageOutput::create([
                        'batch_stage_execution_id' => $execution->id,
                        'output_type' => $out['output_type'],
                        'material_id' => $out['material_id'] ?? null,
                        'product_id' => $out['product_id'] ?? null,
                        'output_name' => $out['output_name'],
                        'actual_qty' => $out['actual_qty'],
                        'uom_id' => $out['uom_id'],
                        'lot_or_wip_no' => $out['lot_or_wip_no'] ?? null,
                        'destination_warehouse_id' => $out['destination_warehouse_id'] ?? null,
                        'qc_required' => !empty($out['qc_required']),
                        'remarks' => $out['remarks'] ?? null,
                    ]);

                    // If output is WIP, auto-create WIP Record
                    if ($out['output_type'] === 'WIP') {
                        WipRecord::create([
                            'wip_number' => $this->docService->generate('WIP Number'),
                            'batch_id' => $execution->batch_id,
                            'source_stage_id' => $execution->recipe_stage_id,
                            'source_execution_id' => $execution->id,
                            'material_id' => $out['material_id'] ?? null,
                            'product_id' => $out['product_id'] ?? null,
                            'material_name' => $out['output_name'],
                            'generated_qty' => $out['actual_qty'],
                            'consumed_qty' => 0,
                            'available_qty' => $out['actual_qty'],
                            'uom_id' => $out['uom_id'],
                            'qc_status' => 'Approved',
                            'warehouse_id' => $out['destination_warehouse_id'] ?? $execution->batch->productionPlan->warehouse_id,
                            'status' => 'Available',
                        ]);
                    }

                    // If output is Recovered Solvent, create Recovery Lot
                    if ($out['output_type'] === 'Recovered' && !empty($out['material_id'])) {
                        RecoveryLot::create([
                            'recovery_lot_number' => $this->docService->generate('Recovery Lot Number'),
                            'source_batch_id' => $execution->batch_id,
                            'source_stage_id' => $execution->recipe_stage_id,
                            'material_id' => $out['material_id'],
                            'generated_qty' => $out['actual_qty'],
                            'available_qty' => $out['actual_qty'],
                            'reserved_qty' => 0,
                            'consumed_qty' => 0,
                            'sold_qty' => 0,
                            'remaining_qty' => $out['actual_qty'],
                            'warehouse_id' => $out['destination_warehouse_id'] ?? $execution->batch->productionPlan->warehouse_id,
                            'recovery_date' => now()->toDateString(),
                            'qc_status' => 'Approved',
                            'status' => 'Active',
                        ]);
                    }

                    // If output is Spent or Waste, create Waste Record
                    if (in_array($out['output_type'], ['Spent', 'Waste'])) {
                        WasteRecord::create([
                            'batch_id' => $execution->batch_id,
                            'stage_id' => $execution->recipe_stage_id,
                            'waste_type' => $out['output_type'] === 'Spent' ? 'Spent Material' : 'Waste Material',
                            'item_name' => $out['output_name'],
                            'quantity' => $out['actual_qty'],
                            'uom_id' => $out['uom_id'],
                            'warehouse_id' => $out['destination_warehouse_id'] ?? null,
                            'disposal_date' => now()->toDateString(),
                            'created_by' => Auth::id(),
                        ]);
                    }
                }
            }

            $endTime = now();
            $execution->end_time = $endTime;
            if ($execution->start_time) {
                $execution->duration_minutes = Carbon::parse($execution->start_time)->diffInMinutes($endTime);
            }
            $execution->completed_at = $endTime;
            $execution->supervisor_remarks = $supervisorRemarks;

            // Check if QC is mandatory for this stage
            if ($execution->recipeStage->qc_required) {
                $execution->status = 'QC Pending';
                $execution->qc_status = 'Pending';
                $execution->save();

                // Create QC Request
                $qcReq = QcRequest::create([
                    'qc_request_number' => $this->docService->generate('QC Request Number'),
                    'batch_id' => $execution->batch_id,
                    'batch_stage_execution_id' => $execution->id,
                    'stage_name' => $execution->stage_name,
                    'request_date' => now(),
                    'submitted_by' => Auth::id(),
                    'status' => 'Pending',
                ]);

                // Create QC Request Results from recipe QC mapping
                foreach ($execution->recipeStage->qcTests as $qcTest) {
                    QcRequestResult::create([
                        'qc_request_id' => $qcReq->id,
                        'qc_test_id' => $qcTest->qc_test_id,
                        'test_name' => $qcTest->qcTest->test_name,
                        'test_method' => $qcTest->qcTest->test_method,
                        'result_type' => $qcTest->qcTest->result_type,
                        'uom' => $qcTest->qcTest->uom,
                        'expected_min' => $qcTest->min_value,
                        'expected_max' => $qcTest->max_value,
                        'expected_value' => $qcTest->target_value,
                    ]);
                }

                $batch = $execution->batch;
                $batch->status = 'QC Pending';
                $batch->save();

                NotificationService::notifyRole('qc_manager', 'QC Pending', "Batch {$batch->batch_number} - Stage {$execution->stage_name} submitted for QC review.", route('qc.review', $qcReq->id), 'qc_pending');
            } else {
                $execution->status = 'Completed';
                $execution->save();

                // Unlock next stage if exists
                $nextExec = BatchStageExecution::where('batch_id', $execution->batch_id)
                    ->where('sequence_no', '>', $execution->sequence_no)
                    ->where('status', 'Pending')
                    ->orderBy('sequence_no')
                    ->first();

                if ($nextExec) {
                    $nextExec->status = 'In Progress';
                    $nextExec->start_time = now();
                    $nextExec->save();
                }
            }

            AuditService::log('Production', 'Complete Stage', "Batch: {$execution->batch->batch_number}, Stage: {$execution->stage_name}", null, [
                'execution_id' => $execution->id,
                'status' => $execution->status,
                'duration_minutes' => $execution->duration_minutes,
            ], 'Completed stage execution');
        });
    }

    /**
     * Batch closure & reconciliation validation (Validations 21 & 22).
     */
    public function validateBatchClosure(Batch $batch): array
    {
        $errors = [];

        // Validation 21: Batch closure with pending mandatory stages blocked
        $pendingMandatory = $batch->stageExecutions()
            ->whereHas('recipeStage', fn($q) => $q->where('is_mandatory', true))
            ->whereNotIn('status', ['Completed', 'Approved'])
            ->get();

        if ($pendingMandatory->count() > 0) {
            $names = $pendingMandatory->pluck('stage_name')->implode(', ');
            $errors[] = "Validation Error (21): Cannot close batch while mandatory stages are incomplete: {$names}";
        }

        // Validation 22: Batch closure with mandatory QC pending blocked
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
            $totalInput = (float)($reconciliationData['raw_material_qty'] ?? 0)
                + (float)($reconciliationData['water_qty'] ?? 0)
                + (float)($reconciliationData['chemical_qty'] ?? 0)
                + (float)($reconciliationData['solvent_qty'] ?? 0)
                + (float)($reconciliationData['other_input_qty'] ?? 0);

            $totalOutput = (float)($reconciliationData['fg_qty'] ?? 0)
                + (float)($reconciliationData['wip_qty'] ?? 0)
                + (float)($reconciliationData['cake_qty'] ?? 0)
                + (float)($reconciliationData['liquid_qty'] ?? 0)
                + (float)($reconciliationData['recovered_solvent_qty'] ?? 0)
                + (float)($reconciliationData['by_product_qty'] ?? 0)
                + (float)($reconciliationData['spent_qty'] ?? 0)
                + (float)($reconciliationData['waste_qty'] ?? 0);

            $returns = (float)($reconciliationData['material_returns_qty'] ?? 0);

            // Unaccounted difference = Total Input - (Total Output + Material Returns)
            $difference = $totalInput - ($totalOutput + $returns);

            $recon = BatchReconciliation::updateOrCreate(
                ['batch_id' => $batch->id],
                [
                    'total_input_qty' => $totalInput,
                    'raw_material_qty' => $reconciliationData['raw_material_qty'] ?? 0,
                    'water_qty' => $reconciliationData['water_qty'] ?? 0,
                    'chemical_qty' => $reconciliationData['chemical_qty'] ?? 0,
                    'solvent_qty' => $reconciliationData['solvent_qty'] ?? 0,
                    'other_input_qty' => $reconciliationData['other_input_qty'] ?? 0,
                    'total_output_qty' => $totalOutput,
                    'fg_qty' => $reconciliationData['fg_qty'] ?? 0,
                    'wip_qty' => $reconciliationData['wip_qty'] ?? 0,
                    'cake_qty' => $reconciliationData['cake_qty'] ?? 0,
                    'liquid_qty' => $reconciliationData['liquid_qty'] ?? 0,
                    'recovered_solvent_qty' => $reconciliationData['recovered_solvent_qty'] ?? 0,
                    'by_product_qty' => $reconciliationData['by_product_qty'] ?? 0,
                    'spent_qty' => $reconciliationData['spent_qty'] ?? 0,
                    'waste_qty' => $reconciliationData['waste_qty'] ?? 0,
                    'material_returns_qty' => $returns,
                    'unaccounted_difference_qty' => $difference,
                    'reconciled_at' => now(),
                    'reconciled_by' => $userId,
                    'remarks' => $reconciliationData['remarks'] ?? null,
                ]
            );

            $batch->actual_qty = (float)($reconciliationData['fg_qty'] ?? 0);
            $batch->actual_end_time = now();
            $batch->status = 'Completed';
            $batch->save();

            AuditService::log('Production', 'Close Batch', "Batch: {$batch->batch_number}", null, [
                'actual_qty' => $batch->actual_qty,
                'difference_qty' => $difference,
                'status' => 'Completed',
            ], 'Reconciled and closed batch');

            return $recon;
        });
    }
}
