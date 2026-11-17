<?php

namespace App\Services;

use App\Models\QcRequest;
use App\Models\Batch;
use App\Models\BatchStageExecution;
use App\Models\BatchActivityExecution;
use App\Models\BatchParameterReading;
use App\Models\ReworkRecord;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QcService
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    /**
     * Approve QC request: unlocks next stage.
     */
    public function approve(QcRequest $qcRequest, int $userId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($qcRequest, $userId, $remarks) {
            $qcRequest->status = 'Approved';
            $qcRequest->decision_at = now();
            $qcRequest->decision_by = $userId;
            $qcRequest->decision_remarks = $remarks;
            $qcRequest->save();

            $stageExec = $qcRequest->stageExecution;
            if ($stageExec) {
                $stageExec->qc_status = 'Approved';
                $stageExec->save();

                $prodService = app(ProductionService::class);
                $eval = $prodService->evaluateStageCompletion($stageExec);
                if (empty($eval['operational_unmet'])) {
                    $stageExec->status = 'Completed';
                    if (!$stageExec->completed_at) {
                        $stageExec->completed_at = now();
                    }
                    $stageExec->save();

                    // Unlock the next stage if all requirements are satisfied
                    $prodService->unlockNextStageIfEligible($stageExec);
                } else {
                    $stageExec->status = 'In Progress';
                    $stageExec->save();
                }
            }

            $batch = $qcRequest->batch;
            // If all pending stages done or no active hold/reject, return to In Progress
            if ($batch->status === 'QC Pending') {
                $batch->status = 'In Progress';
                $batch->save();
            }

            AuditService::log('QC', 'Approve QC Request', "Request: {$qcRequest->qc_request_number}", null, [
                'batch' => $batch->batch_number,
                'stage' => $qcRequest->stage_name,
            ], $remarks);
        });
    }

    /**
     * Reject QC request (Validation 15): Rejects entire batch!
     */
    public function reject(QcRequest $qcRequest, int $userId, string $reason): void
    {
        DB::transaction(function () use ($qcRequest, $userId, $reason) {
            $qcRequest->status = 'Rejected';
            $qcRequest->decision_at = now();
            $qcRequest->decision_by = $userId;
            $qcRequest->decision_remarks = $reason;
            $qcRequest->save();

            $stageExec = $qcRequest->stageExecution;
            if ($stageExec) {
                $stageExec->qc_status = 'Rejected';
                $stageExec->status = 'Completed';
                $stageExec->save();
            }

            // Validation 15: QC rejection -> Entire batch rejected
            $batch = $qcRequest->batch;
            $batch->status = 'Rejected';
            $batch->remarks = ($batch->remarks ? $batch->remarks . ' | ' : '') . "QC Rejected: {$reason}";
            $batch->save();

            NotificationService::notifyRole('production_supervisor', 'QC Rejected', "Batch {$batch->batch_number} has been REJECTED by QC: {$reason}", route('production.batches.show', $batch->id), 'qc_rejected');

            AuditService::log('QC', 'Reject Batch via QC', "Batch: {$batch->batch_number}", null, [
                'qc_request' => $qcRequest->qc_request_number,
                'reason' => $reason,
                'batch_status' => 'Rejected',
            ], $reason);
        });
    }

    /**
     * Request Rework (Validations 16, 17):
     * Creates rework instruction, creates a NEW stage execution record, preserves the original execution.
     */
    public function requestRework(QcRequest $qcRequest, int $userId, string $instructions, string $reworkReason): ReworkRecord
    {
        return DB::transaction(function () use ($qcRequest, $userId, $instructions, $reworkReason) {
            $qcRequest->status = 'Rework Required';
            $qcRequest->decision_at = now();
            $qcRequest->decision_by = $userId;
            $qcRequest->decision_remarks = $reworkReason;
            $qcRequest->rework_instructions = $instructions;
            $qcRequest->save();

            $origExec = $qcRequest->stageExecution;
            $origExec->qc_status = 'Rework Required';
            $origExec->save();

            $batch = $qcRequest->batch;
            $batch->status = 'Rework Required';
            $batch->save();

            // Next execution number for this stage
            $maxExecNum = BatchStageExecution::where('batch_id', $batch->id)
                ->where('recipe_stage_id', $origExec->recipe_stage_id)
                ->max('execution_number') ?? 1;

            $newExecNum = $maxExecNum + 1;

            // Validation 16: Rework execution creates NEW execution record
            // Validation 17: Original stage record must remain unchanged
            $newExec = BatchStageExecution::create([
                'batch_id' => $batch->id,
                'recipe_stage_id' => $origExec->recipe_stage_id,
                'execution_number' => $newExecNum,
                'stage_name' => $origExec->stage_name . " (Rework #{$newExecNum})",
                'sequence_no' => $origExec->sequence_no,
                'occurrence_no' => $origExec->occurrence_no,
                'execution_type' => 'Rework',
                'status' => 'In Progress',
                'qc_status' => 'Pending',
                'equipment_id' => $origExec->equipment_id,
                'actual_operator_name' => $origExec->actual_operator_name,
                'operator_count' => $origExec->operator_count,
                'start_time' => now(),
            ]);

            // Copy activities to new execution
            foreach ($origExec->recipeStage->activities as $act) {
                BatchActivityExecution::create([
                    'batch_stage_execution_id' => $newExec->id,
                    'recipe_stage_activity_id' => $act->id,
                    'activity_name' => $act->activity->name,
                    'sequence_no' => $act->sequence_no,
                    'status' => 'Pending',
                ]);
            }

            // Copy parameters to new execution
            foreach ($origExec->recipeStage->parameters as $param) {
                BatchParameterReading::create([
                    'batch_stage_execution_id' => $newExec->id,
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

            // Create Rework Record
            $rework = ReworkRecord::create([
                'rework_number' => $this->docService->generate('Rework Number'),
                'batch_id' => $batch->id,
                'original_stage_execution_id' => $origExec->id,
                'rework_stage_execution_id' => $newExec->id,
                'rework_type' => 'Stage Rework',
                'rework_reason' => $reworkReason,
                'qc_request_id' => $qcRequest->id,
                'instructions' => $instructions,
                'requested_by' => $userId,
                'start_date' => now(),
                'status' => 'In Progress',
            ]);

            NotificationService::notifyRole('production_supervisor', 'QC Rework Required', "Batch {$batch->batch_number} - Stage {$origExec->stage_name} requires rework: {$instructions}", route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $newExec->id]), 'qc_rework');

            AuditService::log('QC', 'Request Rework', "Rework: {$rework->rework_number}", null, [
                'batch' => $batch->batch_number,
                'orig_exec_id' => $origExec->id,
                'new_exec_id' => $newExec->id,
                'instructions' => $instructions,
            ], $reworkReason);

            return $rework;
        });
    }
}
