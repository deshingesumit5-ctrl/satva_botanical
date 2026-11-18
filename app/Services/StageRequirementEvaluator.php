<?php

namespace App\Services;

use App\Models\BatchMaterialConsumption;
use App\Models\BatchStageExecution;
use App\Models\ReworkRecord;
use Carbon\Carbon;

class StageRequirementEvaluator
{
    /**
     * Evaluate all applicable Section 7.2 completion conditions for a stage execution
     * and return structured items with met status, current/required values, reason ("Why"),
     * action ("What to do"), and UI anchor.
     */
    public function evaluate(BatchStageExecution $execution): array
    {
        $execution->loadMissing([
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

        $recipeStage = $execution->recipeStage;
        $checklist = [];
        $operationalUnmet = [];
        $allUnmet = [];

        if ($execution->status === 'Skipped' && $recipeStage && ($recipeStage->is_optional || $recipeStage->is_conditional)) {
            return [
                'can_complete'          => true,
                'can_unlock_next_stage' => true,
                'qc_required'           => false,
                'checklist'             => [[
                    'key'          => 'skipped',
                    'label'        => 'Optional / Conditional stage skipped',
                    'status'       => 'Skipped',
                    'met'          => true,
                    'current'      => 'Skipped',
                    'required'     => 'Skipped',
                    'reason'       => null,
                    'action'       => null,
                    'anchor'       => '#stageExecutionForm',
                    'anchor_label' => 'Go to Stage',
                ]],
                'operational_unmet'     => [],
                'unmet_reasons'         => [],
                'met_count'             => 1,
                'total_count'           => 1,
                'unmet_count'           => 0,
                'summary'               => 'All requirements met. Optional/conditional stage skipped.',
            ];
        }

        $isAlreadyCompleted = in_array($execution->status, ['Completed', 'Approved'], true);

        // 1. Base Mandatory Check: Required Activities with Start & End Times
        if ($recipeStage) {
            $mandatoryActs = $recipeStage->activities->where('is_mandatory', true);
            $totalMandActs = $mandatoryActs->count();

            if ($totalMandActs > 0) {
                $completedMandActs = 0;
                $missingReasons = [];
                $missingNames = [];

                $hasAnyRecordedActTiming = $execution->activityExecutions
                    ->filter(fn ($a) => !empty($a->start_datetime) || !empty($a->start_time) || !empty($a->end_datetime) || !empty($a->end_time))
                    ->isNotEmpty();

                if ($isAlreadyCompleted && !$hasAnyRecordedActTiming) {
                    $completedMandActs = $totalMandActs;
                } else {
                    foreach ($mandatoryActs as $mandAct) {
                        $actExec = $execution->activityExecutions->firstWhere('recipe_stage_activity_id', $mandAct->id);
                        $actName = $actExec?->activity_name ?? $mandAct->activity?->name ?? 'Activity';
                        $hasStart = $actExec && (!empty($actExec->start_datetime) || !empty($actExec->start_time));
                        $hasEnd = $actExec && (!empty($actExec->end_datetime) || !empty($actExec->end_time));

                        if ($hasStart && $hasEnd) {
                            $completedMandActs++;
                        } elseif ($hasStart && !$hasEnd) {
                            $missingNames[] = $actName;
                            $missingReasons[] = "Activity '{$actName}' has no end time.";
                        } elseif (!$hasStart && $hasEnd) {
                            $missingNames[] = $actName;
                            $missingReasons[] = "Activity '{$actName}' has no start time.";
                        } else {
                            $missingNames[] = $actName;
                            $missingReasons[] = "Activity '{$actName}' has no start and end time.";
                        }
                    }
                }

                $met = $completedMandActs >= $totalMandActs;
                $label = "Required activities with start/end times: {$completedMandActs} of {$totalMandActs}: " . ($met ? 'met' : 'not met');
                $checklist[] = [
                    'key'          => 'activities',
                    'label'        => $label,
                    'met'          => $met,
                    'current'      => $completedMandActs,
                    'required'     => $totalMandActs,
                    'reason'       => $met ? null : implode(' ', $missingReasons),
                    'action'       => $met ? null : ("Enter the start and end date-time for '" . implode("', '", $missingNames) . "' in Section B."),
                    'anchor'       => '#section-b',
                    'anchor_label' => 'Go to Section B',
                ];

                if (!$met) {
                    $operationalUnmet[] = $label;
                    $allUnmet[] = $label;
                }
            }
        }

        // 2. Base Mandatory Check: Mandatory Parameter Readings Entered
        if ($recipeStage) {
            $mandatoryParams = $recipeStage->parameters->where('is_mandatory', true);
            $totalMandParams = $mandatoryParams->count();

            if ($totalMandParams > 0) {
                $enteredMandParams = 0;
                $missingParamNames = [];
                $missingParamReasons = [];

                $hasAnyRecordedReading = $execution->parameterReadings
                    ->filter(fn ($r) => $r->actual_value !== null && trim((string) $r->actual_value) !== '')
                    ->isNotEmpty();

                if ($isAlreadyCompleted && !$hasAnyRecordedReading) {
                    $enteredMandParams = $totalMandParams;
                } else {
                    foreach ($mandatoryParams as $mandParam) {
                        $reading = $execution->parameterReadings->firstWhere('recipe_parameter_id', $mandParam->id);
                        $paramName = $reading?->parameter_name ?? $mandParam->parameter?->name ?? 'Parameter';
                        $hasVal = $reading && $reading->actual_value !== null && trim((string) $reading->actual_value) !== '';
                        if ($hasVal) {
                            $enteredMandParams++;
                        } else {
                            $missingParamNames[] = $paramName;
                            $missingParamReasons[] = "Reading for '{$paramName}' is missing.";
                        }
                    }
                }

                $met = $enteredMandParams >= $totalMandParams;
                $label = "Mandatory parameter readings: {$enteredMandParams} of {$totalMandParams} entered: " . ($met ? 'met' : 'not met');
                $checklist[] = [
                    'key'          => 'mandatory_readings',
                    'label'        => $label,
                    'met'          => $met,
                    'current'      => $enteredMandParams,
                    'required'     => $totalMandParams,
                    'reason'       => $met ? null : implode(' ', $missingParamReasons),
                    'action'       => $met ? null : "Enter the actual value in Section C.",
                    'anchor'       => '#section-c',
                    'anchor_label' => 'Go to Section C',
                ];

                if (!$met) {
                    $operationalUnmet[] = $label;
                    $allUnmet[] = $label;
                }
            }
        }

        // 3. Base Mandatory Check: Material Consumption Recorded
        if ($recipeStage) {
            $mandatoryInputs = $recipeStage->inputMaterials->where('is_mandatory', true);
            $reqInputCount = $mandatoryInputs->count();

            if ($reqInputCount > 0) {
                $consumptions = BatchMaterialConsumption::where('batch_id', $execution->batch_id)
                    ->where('recipe_stage_id', $recipeStage->id)
                    ->get();

                $recordedInputs = 0;
                $missingMaterials = [];

                foreach ($mandatoryInputs as $inp) {
                    $cons = $consumptions->firstWhere('material_id', $inp->material_id);
                    if ($cons && (float) $cons->actual_consumed_qty > 0) {
                        $recordedInputs++;
                    } else {
                        $missingMaterials[] = $inp->material?->name ?? ("Material #{$inp->material_id}");
                    }
                }

                if ($isAlreadyCompleted && $consumptions->where('actual_consumed_qty', '>', 0)->count() === 0) {
                    $recordedInputs = $reqInputCount;
                    $missingMaterials = [];
                }

                $met = $recordedInputs >= $reqInputCount;
                $label = "Material consumption recorded ({$recordedInputs} of {$reqInputCount}): " . ($met ? 'met' : 'not met');
                $reason = null;
                if (!$met) {
                    $reason = count($missingMaterials) === 1
                        ? "Actual charged quantity for '{$missingMaterials[0]}' is missing or zero."
                        : "Actual charged quantities for '" . implode("', '", $missingMaterials) . "' are missing or zero.";
                }

                $checklist[] = [
                    'key'          => 'material_consumption',
                    'label'        => $label,
                    'met'          => $met,
                    'current'      => $recordedInputs,
                    'required'     => $reqInputCount,
                    'reason'       => $reason,
                    'action'       => $met ? null : 'Enter the actual charged quantity in Section D.',
                    'anchor'       => '#section-d',
                    'anchor_label' => 'Go to Section D',
                ];

                if (!$met) {
                    $operationalUnmet[] = $label;
                    $allUnmet[] = $label;
                }
            }
        }

        // 4. Base Mandatory Check: Mandatory Stage Outputs Recorded
        if ($recipeStage) {
            $mandatoryOutputs = $recipeStage->outputMaterials->where('is_mandatory', true);
            $reqOutputCount = $mandatoryOutputs->count();
            if ($reqOutputCount > 0) {
                $recordedOutputs = $execution->outputs->where('actual_qty', '>', 0)->count();
                if ($isAlreadyCompleted && $recordedOutputs === 0) {
                    $recordedOutputs = 1;
                }
                $met = $recordedOutputs > 0;
                $label = "Stage output quantities recorded: " . ($met ? 'met' : 'not met');
                $checklist[] = [
                    'key'          => 'stage_outputs',
                    'label'        => $label,
                    'met'          => $met,
                    'current'      => $recordedOutputs,
                    'required'     => 1,
                    'reason'       => $met ? null : 'Output quantity is missing or zero.',
                    'action'       => $met ? null : 'Enter the output quantity in Section E.',
                    'anchor'       => '#section-e',
                    'anchor_label' => 'Go to Section E',
                ];

                if (!$met) {
                    $operationalUnmet[] = $label;
                    $allUnmet[] = $label;
                }
            }
        }

        // 5. Section 7.2 Condition 1: Completion of a specified number of repetitions
        $minReps = max(1, (int) ($recipeStage->min_repetitions ?? 1));
        if ($minReps > 1 || ($recipeStage && $recipeStage->is_repeatable)) {
            $allStageExecs = BatchStageExecution::where('batch_id', $execution->batch_id)
                ->where('recipe_stage_id', $execution->recipe_stage_id)
                ->orderBy('execution_number')
                ->get();

            $totalRepsCount = $allStageExecs->count();
            $priorIncomplete = $allStageExecs
                ->where('id', '!=', $execution->id)
                ->where('execution_number', '<', $execution->execution_number)
                ->whereNotIn('status', ['Completed', 'Approved'])
                ->count();

            $met = ($totalRepsCount >= $minReps) && ($priorIncomplete === 0);
            $label = "Repetitions {$totalRepsCount} of {$minReps}: " . ($met ? 'met' : 'not met');

            $reason = null;
            $action = null;
            $anchor = '#add-repetition-btn';
            $anchorLabel = 'Go to Add Repetition';

            if (!$met) {
                if ($totalRepsCount < $minReps) {
                    $remaining = $minReps - $totalRepsCount;
                    $reason = "{$totalRepsCount} of {$minReps} repetitions completed.";
                    $action = "Add {$remaining} more repetition" . ($remaining > 1 ? 's' : '') . " using Add Repetition.";
                } else {
                    $reason = "{$priorIncomplete} earlier repetition(s) for this stage are not yet completed.";
                    $action = "Complete all earlier repetitions in Stage Repetition History before completing this execution.";
                    $anchor = '#stage-repetition-history';
                    $anchorLabel = 'Go to Repetition History';
                }
            }

            $checklist[] = [
                'key'          => 'repetitions',
                'label'        => $label,
                'met'          => $met,
                'current'      => $totalRepsCount,
                'required'     => $minReps,
                'reason'       => $reason,
                'action'       => $action,
                'anchor'       => $anchor,
                'anchor_label' => $anchorLabel,
            ];

            if (!$met) {
                $operationalUnmet[] = $label;
                $allUnmet[] = $label;
            }
        }

        // 6. Section 7.2 Condition 2: Satisfaction of parameter acceptance criteria (and Deviation Action = Block)
        if ($recipeStage) {
            $acceptanceParamIds = is_array($recipeStage->acceptance_parameter_ids)
                ? array_map('intval', $recipeStage->acceptance_parameter_ids)
                : [];

            foreach ($recipeStage->parameters as $rp) {
                if ($rp->deviation_action === 'Block' && !in_array((int) $rp->parameter_id, $acceptanceParamIds, true)) {
                    $acceptanceParamIds[] = (int) $rp->parameter_id;
                }
            }

            foreach ($acceptanceParamIds as $paramId) {
                $rp = $recipeStage->parameters->firstWhere('parameter_id', $paramId);
                $reading = $execution->parameterReadings->first(function ($r) use ($paramId, $rp) {
                    if ($rp && (int) $r->recipe_parameter_id === (int) $rp->id) {
                        return true;
                    }
                    return $r->recipeParameter && (int) $r->recipeParameter->parameter_id === (int) $paramId;
                });

                $paramName = $reading?->parameter_name ?? $rp?->parameter?->name ?? "Parameter #{$paramId}";
                $min = $reading?->min_value ?? $rp?->min_value;
                $max = $reading?->max_value ?? $rp?->max_value;
                $actual = $reading?->actual_value;

                $rangeStr = ($min !== null && $min !== '' ? $min : '*') . '-' . ($max !== null && $max !== '' ? $max : '*');
                $met = false;
                $hasActual = $actual !== null && trim((string) $actual) !== '';

                if ($isAlreadyCompleted && !$hasActual) {
                    $met = true;
                } elseif ($hasActual && is_numeric($actual)) {
                    $val = (float) $actual;
                    $met = true;
                    if ($min !== null && $min !== '' && is_numeric($min) && $val < (float) $min) {
                        $met = false;
                    }
                    if ($max !== null && $max !== '' && is_numeric($max) && $val > (float) $max) {
                        $met = false;
                    }
                }

                $actualDisplay = $hasActual ? $actual : 'not entered';
                $label = "{$paramName} {$actualDisplay}, accepted range {$rangeStr}: " . ($met ? 'met' : 'not met');

                $reason = null;
                $action = null;
                if (!$met) {
                    if (!$hasActual) {
                        $reason = "Reading for '{$paramName}' is missing (accepted range {$rangeStr}).";
                        $action = "Enter the actual value within {$rangeStr} in Section C.";
                    } else {
                        $reason = "{$paramName} entered {$actual}, accepted range {$rangeStr}.";
                        $action = "Check the reading and enter a corrected new reading within range, or follow the recipe deviation action (request QC or rework).";
                    }
                }

                $checklist[] = [
                    'key'          => "param_accept_{$paramId}",
                    'label'        => $label,
                    'met'          => $met,
                    'current'      => $actualDisplay,
                    'required'     => $rangeStr,
                    'reason'       => $reason,
                    'action'       => $action,
                    'anchor'       => '#section-c',
                    'anchor_label' => 'Go to Section C',
                ];

                if (!$met) {
                    $operationalUnmet[] = $label;
                    $allUnmet[] = $label;
                }
            }
        }

        // 7. Section 7.2 Condition 3: Achievement of expected process conditions
        if ($recipeStage && !empty($recipeStage->expected_process_conditions) && is_array($recipeStage->expected_process_conditions)) {
            foreach ($recipeStage->expected_process_conditions as $idx => $cond) {
                $paramId = $cond['parameter_id'] ?? null;
                $paramName = $cond['parameter_name'] ?? 'Condition #' . ($idx + 1);
                $operator = $cond['operator'] ?? '=';
                $targetVal = $cond['value'] ?? null;
                $maxVal = $cond['max_value'] ?? null;
                $reqHoldMins = !empty($cond['hold_time_minutes']) ? (int) $cond['hold_time_minutes'] : null;

                $reading = $execution->parameterReadings->first(function ($r) use ($paramId, $paramName) {
                    if ($paramId && $r->recipeParameter && (int) $r->recipeParameter->parameter_id === (int) $paramId) {
                        return true;
                    }
                    return strtolower((string) $r->parameter_name) === strtolower((string) $paramName);
                });

                if ($reading && $reading->parameter_name) {
                    $paramName = $reading->parameter_name;
                }

                $actual = $reading?->actual_value;
                $hasActual = $actual !== null && trim((string) $actual) !== '';
                $valueMet = false;

                if ($hasActual) {
                    if (is_numeric($actual) && is_numeric($targetVal)) {
                        $a = (float) $actual;
                        $t = (float) $targetVal;
                        $valueMet = match ($operator) {
                            '='       => abs($a - $t) < 0.0001,
                            '>'       => $a > $t,
                            '>='      => $a >= $t,
                            '<'       => $a < $t,
                            '<='      => $a <= $t,
                            'between' => $a >= $t && ($maxVal !== null && is_numeric($maxVal) ? $a <= (float) $maxVal : true),
                            default   => $a == $t,
                        };
                    } else {
                        $valueMet = strtolower(trim((string) $actual)) === strtolower(trim((string) $targetVal));
                    }
                }

                $holdMet = true;
                $actualHold = (int) ($execution->actual_hold_time_minutes ?? $execution->duration_minutes ?? 0);
                if ($reqHoldMins !== null && $reqHoldMins > 0) {
                    $holdMet = $actualHold >= $reqHoldMins;
                }

                $met = $valueMet && $holdMet;
                $condExpr = $operator === 'between' ? "between {$targetVal} and {$maxVal}" : "{$operator} {$targetVal}";
                $condSpec = $condExpr;
                if ($reqHoldMins) {
                    $condSpec .= " (hold {$reqHoldMins} min, actual {$actualHold} min)";
                }
                $actualStr = $hasActual ? $actual : 'not entered';
                $label = "Process condition {$paramName} {$condSpec} (actual: {$actualStr}): " . ($met ? 'met' : 'not met');

                $reason = null;
                $action = null;
                $anchor = '#section-c';
                $anchorLabel = 'Go to Section C';

                if (!$met) {
                    $holdPart = $reqHoldMins ? " for {$reqHoldMins} minutes" : '';
                    $valPart = $hasActual ? "; last value {$actual}" : "; no reading recorded";
                    $holdExtra = ($reqHoldMins && !$holdMet) ? " (actual hold time {$actualHold} of {$reqHoldMins} min)" : '';
                    $reason = "{$paramName} must be {$condExpr}{$holdPart}{$valPart}{$holdExtra}.";

                    if (!$valueMet && $reqHoldMins && !$holdMet) {
                        $action = "Record a {$paramName} reading {$condExpr} in Section C and enter actual hold time >= {$reqHoldMins} minutes in Section A.";
                    } elseif (!$valueMet) {
                        $action = "Record a {$paramName} reading {$condExpr} in Section C.";
                    } else {
                        $action = "Enter actual hold time >= {$reqHoldMins} minutes in Section A.";
                        $anchor = '#section-a';
                        $anchorLabel = 'Go to Section A';
                    }
                }

                $checklist[] = [
                    'key'          => "process_cond_{$idx}",
                    'label'        => $label,
                    'met'          => $met,
                    'current'      => $actualStr,
                    'required'     => $condSpec,
                    'reason'       => $reason,
                    'action'       => $action,
                    'anchor'       => $anchor,
                    'anchor_label' => $anchorLabel,
                ];

                if (!$met) {
                    $operationalUnmet[] = $label;
                    $allUnmet[] = $label;
                }
            }
        }

        // 8. Section 7.2 Condition 4: Required Quality Approval
        $qcRequired = $recipeStage && (
            $recipeStage->qc_required ||
            $recipeStage->qcTests->where('blocks_next_stage', true)->isNotEmpty()
        );
        if ($recipeStage && !$qcRequired) {
            foreach ($execution->parameterReadings as $r) {
                if (!$r->is_within_range && $r->recipeParameter && $r->recipeParameter->deviation_action === 'QC Approval Required') {
                    $qcRequired = true;
                    break;
                }
            }
        }

        if ($qcRequired) {
            $qcReq = $execution->qcRequest;
            $qcApproved = ($execution->qc_status === 'Approved') || ($qcReq && $qcReq->status === 'Approved');
            $isRejected = ($execution->qc_status === 'Rejected') || ($qcReq && $qcReq->status === 'Rejected');
            $isRework = ($execution->qc_status === 'Rework Required') || ($qcReq && $qcReq->status === 'Rework Required');

            $qcStateText = $qcApproved
                ? 'approved'
                : ($isRework ? 'rework required' : ($isRejected ? 'rejected' : 'pending'));
            $label = "QC approval: {$qcStateText}";

            $reason = null;
            $action = null;
            $anchor = '#qc-status-box';
            $anchorLabel = 'Go to QC Status';
            $qcReqUrl = null;
            $reworkUrl = null;

            if (!$qcApproved) {
                if ($isRejected) {
                    $rejReason = $qcReq?->decision_remarks ?: 'Rejected by QC';
                    $reason = "QC rejected this stage: {$rejReason}. The batch is rejected.";
                    $action = 'Batch is rejected. Review the QC decision and batch record.';
                    $qcReqUrl = $qcReq ? route('qc.show', $qcReq->id) : null;
                } elseif ($isRework) {
                    $rwInst = $qcReq?->rework_instructions ?: $qcReq?->decision_remarks ?: 'Rework required';
                    $reason = "QC asked for rework: {$rwInst}.";
                    $action = 'Start the rework execution.';
                    $reworkRec = $qcReq ? ReworkRecord::where('qc_request_id', $qcReq->id)->latest('id')->first() : null;
                    if ($reworkRec && $reworkRec->rework_stage_execution_id) {
                        $reworkUrl = route('production.execution.show', [
                            'batch'              => $execution->batch_id,
                            'stage_execution_id' => $reworkRec->rework_stage_execution_id,
                        ]);
                    }
                    $qcReqUrl = $qcReq ? route('qc.show', $qcReq->id) : null;
                } elseif ($execution->status === 'QC Pending' || $qcReq) {
                    $dtStr = $qcReq?->request_date
                        ? Carbon::parse($qcReq->request_date)->format('d-m-Y H:i')
                        : ($execution->completed_at ? Carbon::parse($execution->completed_at)->format('d-m-Y H:i') : now()->format('d-m-Y H:i'));
                    $subBy = $qcReq?->submittedByUser?->name ?? $execution->actual_operator_name ?? 'Production';
                    $qcNo = $qcReq?->qc_request_number ?? 'Pending';
                    $reason = "Submitted to QC on {$dtStr} by {$subBy} (QC request {$qcNo}). Waiting for the QC Manager.";
                    $action = 'Ask the QC Manager to review it in QC > Pending Inspections. The next stage unlocks automatically after approval.';
                    $qcReqUrl = $qcReq ? route('qc.show', $qcReq->id) : null;
                } else {
                    $reason = 'Stage requires QC Manager approval before unlocking the next stage.';
                    $action = 'Complete all stage checks and click Complete Stage and Move to Next to submit it for QC inspection.';
                    $anchor = '#completeStageBtn';
                    $anchorLabel = 'Go to Complete Stage';
                }
            }

            $checklist[] = [
                'key'               => 'qc_approval',
                'label'             => $label,
                'met'               => $qcApproved,
                'current'           => $qcStateText,
                'required'          => 'Approved',
                'reason'            => $reason,
                'action'            => $action,
                'anchor'            => $anchor,
                'anchor_label'      => $anchorLabel,
                'qc_request_id'     => $qcReq?->id,
                'qc_request_number' => $qcReq?->qc_request_number,
                'qc_request_url'    => $qcReqUrl,
                'rework_url'        => $reworkUrl,
            ];

            if (!$qcApproved) {
                $allUnmet[] = $label;
            }
        }

        // 9. Section 7.2 Condition 5: Manual Authorization by a Supervisor
        if ($recipeStage && $recipeStage->supervisor_auth_required) {
            $authorized = !empty($execution->supervisor_authorized_by) && !empty($execution->supervisor_authorized_at);
            if ($authorized) {
                $byName = $execution->authorizedByUser?->name ?? 'Supervisor';
                $atStr = Carbon::parse($execution->supervisor_authorized_at)->format('d-m-Y H:i');
                $label = "Supervisor authorization: authorized by {$byName} ({$atStr})";
            } else {
                $label = "Supervisor authorization: not authorized";
            }

            $checklist[] = [
                'key'          => 'supervisor_authorization',
                'label'        => $label,
                'met'          => $authorized,
                'current'      => $authorized ? 'Authorized' : 'Not authorized',
                'required'     => 'Authorized',
                'reason'       => $authorized ? null : 'Not authorized yet.',
                'action'       => $authorized ? null : 'Ask a user with the Authorize Stage permission (for example Production Supervisor) to click Authorize.',
                'anchor'       => '#authorize-stage-btn',
                'anchor_label' => 'Go to Authorize',
            ];

            if (!$authorized) {
                $operationalUnmet[] = $label;
                $allUnmet[] = $label;
            }
        }

        $totalCount = count($checklist);
        $metCount = count(array_filter($checklist, fn ($i) => !empty($i['met'])));
        $unmetItems = array_values(array_filter($checklist, fn ($i) => empty($i['met'])));
        $unmetCount = count($unmetItems);

        $isStageStatusComplete = in_array($execution->status, ['Completed', 'Approved'], true);

        if ($unmetCount > 0) {
            $steps = [];
            foreach ($unmetItems as $idx => $item) {
                $num = $idx + 1;
                $steps[] = "{$num}) " . ($item['action'] ?: $item['label']);
            }
            $summary = "{$metCount} of {$totalCount} requirements met. To move to the next stage: " . implode(' ', $steps);
        } elseif ($isStageStatusComplete) {
            $summary = 'Stage completed — all requirements met. You can proceed to the next stage.';
        } else {
            $summary = 'All requirements met. Click Complete Stage and Move to Next.';
        }

        $canComplete = empty($operationalUnmet) && !in_array($execution->status, ['QC Pending', 'Rework Required', 'Completed', 'Skipped'], true);
        $canUnlockNextStage = $isStageStatusComplete && empty($allUnmet);

        return [
            'can_complete'          => $canComplete,
            'can_unlock_next_stage' => $canUnlockNextStage,
            'qc_required'           => $qcRequired,
            'checklist'             => $checklist,
            'operational_unmet'     => $operationalUnmet,
            'unmet_reasons'         => $allUnmet,
            'met_count'             => $metCount,
            'total_count'           => $totalCount,
            'unmet_count'           => $unmetCount,
            'summary'               => $summary,
        ];
    }
}
