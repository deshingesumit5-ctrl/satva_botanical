<?php

namespace App\Services;

use App\Models\QcRequest;
use App\Models\QcRequestResult;
use App\Models\Batch;
use App\Models\BatchStageExecution;
use App\Models\BatchActivityExecution;
use App\Models\BatchParameterReading;
use App\Models\ReworkRecord;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
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
     * Idempotently create a QC Request for a stage execution submitted to QC.
     */
    public function createForStageExecution(BatchStageExecution $execution, ?int $submittedBy = null): QcRequest
    {
        return DB::transaction(function () use ($execution, $submittedBy) {
            $existing = QcRequest::where('batch_stage_execution_id', $execution->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $execution->loadMissing([
                'batch.product',
                'batch.recipeVersion',
                'recipeStage.stage',
                'recipeStage.qcTests.qcTest',
                'parameterReadings',
                'activityExecutions',
                'outputs.uom',
            ]);

            $readingsSnapshot = [
                'parameters' => $execution->parameterReadings->map(fn ($r) => [
                    'parameter_name'  => $r->parameter_name,
                    'uom'             => $r->uom,
                    'target_value'    => $r->target_value,
                    'min_value'       => $r->min_value,
                    'max_value'       => $r->max_value,
                    'actual_value'    => $r->actual_value,
                    'is_within_range' => (bool) $r->is_within_range,
                ])->values()->all(),
                'activities' => $execution->activityExecutions->map(fn ($a) => [
                    'activity_name'           => $a->activity_name,
                    'start_datetime'          => $a->start_datetime ? Carbon::parse($a->start_datetime)->toDateTimeString() : null,
                    'end_datetime'            => $a->end_datetime ? Carbon::parse($a->end_datetime)->toDateTimeString() : null,
                    'actual_duration_minutes' => $a->actual_duration_minutes,
                    'status'                  => $a->status,
                ])->values()->all(),
            ];

            $consumptions = $execution->batch
                ? $execution->batch->consumptions()->with(['material', 'uom'])->where('recipe_stage_id', $execution->recipe_stage_id)->get()
                : collect();

            $outputsSnapshot = [
                'outputs' => $execution->outputs->map(fn ($o) => [
                    'output_type' => $o->output_type,
                    'output_name' => $o->output_name,
                    'actual_qty'  => (string) $o->actual_qty,
                    'uom'         => $o->uom?->symbol ?? $o->uom?->uom_code ?? '',
                ])->values()->all(),
                'consumptions' => $consumptions->map(fn ($c) => [
                    'material_name'       => $c->material?->name ?? '-',
                    'allocated_qty'       => (string) $c->allocated_qty,
                    'actual_consumed_qty' => (string) $c->actual_consumed_qty,
                    'uom'                 => $c->uom?->symbol ?? $c->uom?->uom_code ?? '',
                ])->values()->all(),
            ];

            $parentRework = ReworkRecord::where('rework_stage_execution_id', $execution->id)->first();
            $parentQcRequestId = $parentRework?->qc_request_id;
            $submitUserId = $submittedBy ?? Auth::id() ?? $execution->batch?->supervisor_id ?? 1;

            $qcReq = QcRequest::create([
                'qc_request_number'        => $this->docService->generate('QC Request Number'),
                'batch_id'                 => $execution->batch_id,
                'batch_stage_execution_id' => $execution->id,
                'product_id'               => $execution->batch?->product_id,
                'recipe_version_id'        => $execution->batch?->recipe_version_id,
                'stage_id'                 => $execution->recipeStage?->stage_id,
                'stage_name'               => $execution->stage_name,
                'request_date'             => $execution->completed_at ?? now(),
                'submitted_by'             => $submitUserId,
                'status'                   => 'Pending',
                'readings_snapshot'        => $readingsSnapshot,
                'outputs_snapshot'         => $outputsSnapshot,
                'parent_qc_request_id'     => $parentQcRequestId,
            ]);

            if ($parentQcRequestId) {
                QcRequest::where('id', $parentQcRequestId)->update(['retest_qc_request_id' => $qcReq->id]);
            }

            if ($execution->recipeStage) {
                foreach ($execution->recipeStage->qcTests as $qcTest) {
                    if ($qcTest->qcTest) {
                        QcRequestResult::create([
                            'qc_request_id'  => $qcReq->id,
                            'qc_test_id'     => $qcTest->qc_test_id,
                            'test_name'      => $qcTest->qcTest->test_name ?? $qcTest->qcTest->name,
                            'test_method'    => $qcTest->qcTest->test_method,
                            'result_type'    => $qcTest->qcTest->result_type ?? 'Numeric',
                            'uom'            => $qcTest->qcTest->uom,
                            'expected_min'   => $qcTest->min_value,
                            'expected_max'   => $qcTest->max_value,
                            'expected_value' => $qcTest->target_value,
                        ]);
                    }
                }
            }

            NotificationService::notifyRole(
                'qc_manager',
                'QC Pending',
                "Batch {$execution->batch?->batch_number} - Stage {$execution->stage_name} submitted for QC review.",
                route('qc.show', $qcReq->id),
                'qc_pending'
            );

            return $qcReq;
        });
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

                    // Mark linked rework record completed if this execution was a rework
                    ReworkRecord::where('rework_stage_execution_id', $stageExec->id)
                        ->where('status', '!=', 'Completed')
                        ->update([
                            'status'   => 'Completed',
                            'end_date' => now(),
                        ]);

                    // Unlock the next stage if all requirements are satisfied
                    $prodService->unlockNextStageIfEligible($stageExec);
                } else {
                    $stageExec->status = 'In Progress';
                    $stageExec->save();
                }
            }

            $batch = $qcRequest->batch;
            // If all pending stages done or no active hold/reject, return to In Progress
            if (in_array($batch->status, ['QC Pending', 'Rework Required'], true)) {
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
