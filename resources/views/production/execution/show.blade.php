@extends('layouts.app')
@section('title', 'Execute Batch: ' . ($batch->batch_number ?? $batch->batch_no))
@section('content')
@php
    $stageLockStates = $stageLockStates ?? [];
    $completionEvaluation = $completionEvaluation ?? ['can_complete' => false, 'checklist' => [], 'operational_unmet' => [], 'unmet_reasons' => [], 'unmet_count' => 0, 'summary' => ''];
    $checklistItems = $completionEvaluation['checklist'] ?? [];
    $canCompleteStage = !empty($completionEvaluation['can_complete']);
    $canAuthorizeStage = auth()->user() && auth()->user()->hasPermission('Authorize Stage');
    $recipeStage = $activeExecution?->recipeStage;
    $stageHistory = $activeExecution
        ? $stageExecutions->where('recipe_stage_id', $activeExecution->recipe_stage_id)->values()
        : collect();
    $isActiveLocked = $activeExecution ? !empty($stageLockStates[$activeExecution->id]['locked']) : true;
    $isReadOnlyStage = !$activeExecution
        || $isActiveLocked
        || in_array($activeExecution->status, ['QC Pending', 'Completed', 'Approved', 'Skipped', 'Rework Required'], true);
    $isEditableInProgress = $activeExecution && !$isReadOnlyStage;
    $canOpenQc = auth()->user() && auth()->user()->hasAnyPermission(['qc.dashboard.view', 'qc.review.view', 'qc.history.view']);
    $nextStageExec = $activeExecution
        ? $stageExecutions->first(function ($se) use ($activeExecution, $stageLockStates) {
            return $se->sequence_no > $activeExecution->sequence_no && empty($stageLockStates[$se->id]['locked']);
        })
        : null;
@endphp
<style>
    .sattva-highlight-pulse {
        animation: sattvaSectionPulse 1.8s ease-out;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.35) !important;
    }
    @keyframes sattvaSectionPulse {
        0% { box-shadow: 0 0 0 5px rgba(220, 53, 69, 0.55); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
</style>
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">Production Execution (BMR Run)</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $batch->status)) }}">{{ $batch->status }}</span>
            </div>
            <p class="page-subtitle">
                Batch: <strong>{{ $batch->batch_number ?? $batch->batch_no }}</strong> |
                Product: {{ $batch->product->name ?? '-' }}
                (Planned: {{ $batch->planned_qty ?? $batch->planned_quantity ?? $batch->batch_size }} {{ $batch->uom->symbol ?? $batch->uom->uom_code ?? 'KG' }})
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.bmr.show', $batch) }}" class="btn btn-outline-info"><i class="bi bi-file-medical me-1"></i> Live BMR</a>
            <a href="{{ route('production.reconciliation.show', $batch) }}" class="btn btn-outline-warning"><i class="bi bi-calculator me-1"></i> Reconciliation</a>
            <a href="{{ route('production.batches.show', $batch) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Batch Overview</a>
        </div>
    </div>

    <!-- Stage Sequence Stepper / Tabs (Section 7.2 Lock Enforcement) -->
    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-2">
            <div class="d-flex overflow-auto gap-2 py-1">
                @foreach($stageExecutions as $se)
                    @php
                        $lockInfo = $stageLockStates[$se->id] ?? ['locked' => false, 'tooltip' => null];
                        $isLocked = !empty($lockInfo['locked']);
                        $isActive = $activeExecution && $activeExecution->id == $se->id;
                        $isDone = in_array($se->status, ['Completed', 'Approved'], true);
                        $isSkipped = $se->status === 'Skipped';
                        $isQcPending = $se->status === 'QC Pending';
                        $isRework = $se->status === 'Rework Required';
                        $displayState = $isLocked ? 'Locked' : ($se->status === 'Pending' ? 'Ready' : $se->status);
                        $stageLabel = $se->stage->name ?? $se->stage->stage_name ?? $se->stage_name ?? 'Stage';
                        if ($se->execution_number > 1) {
                            $stageLabel .= " (#{$se->execution_number})";
                        }
                    @endphp

                    @if($isLocked)
                        <span class="btn btn-sm text-nowrap btn-light text-muted border opacity-75 stage-tab-locked"
                            style="cursor: not-allowed;"
                            data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="{{ $lockInfo['tooltip'] ?? 'Complete previous stage first' }}"
                            aria-disabled="true">
                            <i class="bi bi-lock-fill text-secondary me-1"></i>
                            Seq {{ $se->sequence_no }}: {{ $stageLabel }}
                            <span class="badge bg-secondary-subtle text-secondary ms-1">Locked</span>
                        </span>
                    @else
                        @php
                            $pillClass = $isActive
                                ? 'btn-primary-sattva text-white fw-bold shadow-sm'
                                : ($isDone
                                    ? 'btn-light text-success border-success-subtle'
                                    : ($isQcPending
                                        ? 'btn-warning-subtle text-warning-emphasis border-warning-subtle'
                                        : ($isRework
                                            ? 'btn-danger-subtle text-danger-emphasis border-danger-subtle'
                                            : 'btn-light text-dark border')));
                        @endphp
                        <a href="{{ route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $se->id]) }}"
                            class="btn btn-sm text-nowrap {{ $pillClass }}">
                            @if($isDone)
                                <i class="bi bi-check-circle-fill me-1"></i>
                            @elseif($isQcPending)
                                <i class="bi bi-hourglass-split me-1"></i>
                            @elseif($isRework)
                                <i class="bi bi-arrow-repeat me-1"></i>
                            @elseif($isSkipped)
                                <i class="bi bi-skip-forward-fill me-1"></i>
                            @else
                                <i class="bi bi-play-circle-fill me-1"></i>
                            @endif
                            Seq {{ $se->sequence_no }}: {{ $stageLabel }}
                            <span class="badge {{ $isActive ? 'bg-white text-success' : 'bg-light text-secondary border' }} ms-1">{{ $displayState }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    @if($activeExecution)
    @if($activeExecution->status === 'QC Pending' || $activeExecution->qcRequest)
        <div class="alert {{ $activeExecution->status === 'QC Pending' ? 'alert-warning' : 'alert-info' }} d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4" id="qc-status-box">
            <div>
                <i class="bi {{ $activeExecution->status === 'QC Pending' ? 'bi-hourglass-split' : 'bi-shield-check' }} me-1"></i>
                @if($activeExecution->qcRequest)
                    <strong>QC Request:</strong>
                    @if($canOpenQc)
                        <a href="{{ route('qc.show', $activeExecution->qcRequest) }}" class="fw-bold font-monospace text-decoration-underline">
                            {{ $activeExecution->qcRequest->qc_request_number }}
                        </a>
                    @else
                        <span class="fw-bold font-monospace">{{ $activeExecution->qcRequest->qc_request_number }}</span>
                    @endif
                    <span class="ms-2"><x-status-badge :status="$activeExecution->qcRequest->status" /></span>
                    @if($activeExecution->status === 'QC Pending')
                        <span class="ms-2 small">Waiting for QC Manager approval. Next stage remains locked until QC Approves.</span>
                    @endif
                @else
                    <strong>QC Pending:</strong> Next stage remains locked until QC Approves.
                @endif
            </div>
            @if($activeExecution->qcRequest && $canOpenQc)
                <a href="{{ route('qc.show', $activeExecution->qcRequest) }}" class="btn btn-sm btn-primary-sattva">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open QC Request {{ $activeExecution->qcRequest->qc_request_number }}
                </a>
            @endif
        </div>
    @endif

    @php
        $isChecklistGreen = $canCompleteStage || (in_array($activeExecution->status, ['Completed', 'Approved'], true) && ($completionEvaluation['unmet_count'] ?? 0) === 0);
    @endphp
    <!-- Stage Completion Requirements Checklist Card (Document Section 7.2) -->
    <div class="card card-sattva mb-4 border-start border-4 {{ $isChecklistGreen ? 'border-success' : 'border-warning' }}" id="stageCompletionChecklistCard">
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-clipboard2-check-fill {{ $isChecklistGreen ? 'text-success' : 'text-warning' }} fs-5"></i>
                <div>
                    <h6 class="mb-0 fw-bold text-dark">Stage Completion Requirements</h6>
                    <small class="text-muted">All applicable mandatory conditions must be satisfied before moving to the next stage.</small>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Add Repetition Button (never overwrites original record) -->
                @if(in_array($activeExecution->status, ['In Progress', 'Completed', 'Rework Required'], true))
                    <form action="{{ route('production.execution.add-repetition', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}" method="POST" class="d-inline" id="add-repetition-btn">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Repetition (Current: #{{ $activeExecution->execution_number }})
                        </button>
                    </form>
                @endif

                <!-- Supervisor Authorization Button (Only shown to users with Authorize Stage permission) -->
                @if($recipeStage && $recipeStage->supervisor_auth_required && empty($activeExecution->supervisor_authorized_at))
                    @if($canAuthorizeStage)
                        <form action="{{ route('production.execution.authorize-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}" method="POST" class="d-inline" id="authorize-stage-btn">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-primary-sattva">
                                <i class="bi bi-shield-check me-1"></i> Authorize Stage
                            </button>
                        </form>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border px-3 py-2" id="authorize-stage-btn">
                            <i class="bi bi-lock me-1"></i> Awaits Supervisor Authorization
                        </span>
                    @endif
                @endif

                <!-- Optional / Conditional Stage Skip Button -->
                @if($recipeStage && ($recipeStage->is_optional || $recipeStage->is_conditional) && !in_array($activeExecution->status, ['Completed', 'Skipped', 'QC Pending'], true))
                    <form action="{{ route('production.execution.skip-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-skip-forward me-1"></i> Skip Optional Stage
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-sattva-body p-3" id="stageChecklistCardBody">
            @include('production.execution._checklist', [
                'batch'                => $batch,
                'activeExecution'      => $activeExecution,
                'completionEvaluation' => $completionEvaluation,
                'stageHistory'         => $stageHistory,
            ])
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-secondary me-2">Seq {{ $activeExecution->sequence_no }}</span>
                <span class="fw-bold fs-5 text-dark">{{ $activeExecution->stage->name ?? $activeExecution->stage_name ?? 'Stage' }}</span>
                <span class="badge bg-light text-dark border ms-1">Execution #{{ $activeExecution->execution_number }} ({{ $activeExecution->execution_type }})</span>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $activeExecution->status)) }} ms-2">{{ $activeExecution->status }}</span>
            </div>
            <div class="d-flex gap-2">
                @if(in_array($activeExecution->status, ['Pending', 'Ready'], true))
                    <form action="{{ route('production.execution.start-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-play-fill me-1"></i> Start Stage</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-sattva-body p-4">
            <form id="stageExecutionForm"
                action="{{ route('production.execution.save-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}"
                data-save-url="{{ route('production.execution.save-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}"
                data-complete-url="{{ route('production.execution.complete-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}"
                data-readonly="{{ $isReadOnlyStage ? '1' : '0' }}"
                method="POST">
                @csrf

                <!-- Section A: Line Clearance & Equipment -->
                <div class="mb-4 p-3 bg-light rounded" id="section-a">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-gear-wide-connected me-1"></i> Section A: Line Clearance & Equipment Log</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label required">Vessel / Equipment</label>
                            <select name="equipment_id" class="form-select" {{ $isReadOnlyStage ? 'disabled' : '' }}>
                                <option value="">Select Operating Vessel</option>
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}" {{ $activeExecution->equipment_id == $eq->id ? 'selected' : '' }}>{{ $eq->name }} ({{ $eq->category ?? $eq->equipment_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Line Clearance Performed By</label>
                            <input type="text" name="line_clearance_by" class="form-control" value="{{ $activeExecution->line_clearance_by ?? auth()->user()->name }}" placeholder="Operator name" {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Actual Hold Time (Minutes)</label>
                            <input type="number" min="0" name="actual_hold_time_minutes" class="form-control" value="{{ $activeExecution->actual_hold_time_minutes ?? '' }}" placeholder="e.g. 30" {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cleanliness Verification</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_clean" value="1" id="isClean" {{ $activeExecution->line_clearance_done ? 'checked' : '' }} {{ $isReadOnlyStage ? 'disabled' : '' }}>
                                <label class="form-check-label" for="isClean">Vessel Clean & Free of Prior Product</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section B: Stage Activities & Timings -->
                @if(($activeExecution->activities ?? collect())->count() > 0)
                <div class="mb-4" id="section-b">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-activity me-1"></i> Section B: Process Activities & Execution Timings</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Seq</th>
                                    <th>Activity Name</th>
                                    <th>Mandatory</th>
                                    <th>Start Date & Time</th>
                                    <th>End Date & Time</th>
                                    <th>Operator</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeExecution->activities as $aIdx => $actExec)
                                <tr>
                                    <td>{{ $actExec->sequence_no }}</td>
                                    <td class="fw-semibold">{{ $actExec->activity_name }}</td>
                                    <td>
                                        @if($actExec->recipeStageActivity?->is_mandatory)
                                            <span class="badge bg-danger-subtle text-danger">Mandatory</span>
                                        @else
                                            <span class="badge bg-light text-muted border">Optional</span>
                                        @endif
                                    </td>
                                    <td>
                                        <input type="hidden" name="activities[{{ $actExec->id }}][id]" value="{{ $actExec->id }}">
                                        <input type="datetime-local" name="activities[{{ $actExec->id }}][start_datetime]"
                                            class="form-control form-control-sm"
                                            value="{{ $actExec->start_datetime ? \Carbon\Carbon::parse($actExec->start_datetime)->format('Y-m-d\TH:i') : '' }}"
                                            {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                                    </td>
                                    <td>
                                        <input type="datetime-local" name="activities[{{ $actExec->id }}][end_datetime]"
                                            class="form-control form-control-sm"
                                            value="{{ $actExec->end_datetime ? \Carbon\Carbon::parse($actExec->end_datetime)->format('Y-m-d\TH:i') : '' }}"
                                            {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                                    </td>
                                    <td>
                                        <input type="text" name="activities[{{ $actExec->id }}][operator_name]"
                                            class="form-control form-control-sm"
                                            value="{{ $actExec->operator_name ?? '' }}" placeholder="Operator"
                                            {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                                    </td>
                                    <td id="activity-status-{{ $actExec->id }}">
                                        <x-status-badge :status="$actExec->status" />
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Section C: Monitored Process Parameters -->
                <div class="mb-4" id="section-c">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-speedometer2 me-1"></i> Section C: Monitored Process Parameters (Temp, Pressure, pH)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Target Spec</th>
                                    <th>Accepted Range (Min - Max)</th>
                                    <th width="200">Logged Actual Value *</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($activeExecution->parameterLogs ?? $activeExecution->parameters ?? []) as $idx => $pLog)
                                <tr>
                                    <td class="fw-bold">{{ $pLog->parameter_name ?? $pLog->recipeParameter->parameter->name ?? 'Parameter' }}</td>
                                    <td>{{ $pLog->target_value ?? '-' }} {{ $pLog->uom ?? '' }}</td>
                                    <td>{{ $pLog->min_value ?? '*' }} - {{ $pLog->max_value ?? '*' }} {{ $pLog->uom ?? '' }}</td>
                                    <td>
                                        <input type="hidden" name="parameters[{{ $idx }}][id]" value="{{ $pLog->id }}">
                                        <input type="text" name="parameters[{{ $idx }}][actual_value]" class="form-control form-control-sm" value="{{ $pLog->actual_value }}" placeholder="Enter actual reading" {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                                    </td>
                                    <td id="param-status-{{ $pLog->id }}">
                                        @if($pLog->actual_value !== null && $pLog->actual_value !== '' && $pLog->is_within_range)
                                            <span class="badge bg-success">Within Spec</span>
                                        @elseif($pLog->actual_value !== null && $pLog->actual_value !== '' && !$pLog->is_within_range)
                                            <span class="badge bg-danger">Out of Range ({{ $pLog->actual_value }})</span>
                                        @else
                                            <span class="badge bg-secondary">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-2 text-muted">No parameters configured for this stage in Recipe Step 4.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section D: Material Charges & Consumptions -->
                <div class="mb-4" id="section-d">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-box-arrow-down me-1"></i> Section D: Materials & Solvents Charged</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Material Description</th>
                                    <th>Direction</th>
                                    <th>BOM Standard Qty</th>
                                    <th width="200">Actual Charged Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($activeExecution->consumptions ?? []) as $idx => $cons)
                                <tr>
                                    <td class="fw-bold">{{ $cons->material->name ?? '-' }}</td>
                                    <td><span class="badge bg-light text-dark border">Charge (Input)</span></td>
                                    <td>{{ $cons->allocated_qty ?? $cons->standard_quantity ?? 0 }} {{ $cons->uom->symbol ?? $cons->uom->uom_code ?? '' }}</td>
                                    <td>
                                        <input type="hidden" name="materials[{{ $idx }}][id]" value="{{ $cons->id }}">
                                        <input type="number" step="0.001" name="materials[{{ $idx }}][actual_quantity]" class="form-control form-control-sm" value="{{ $cons->actual_consumed_qty ?? $cons->allocated_qty }}" {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-2 text-muted">No material charges defined for this stage in Recipe Step 5.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section E: Stage Yield & Output Intermediate -->
                <div class="mb-4 p-3 bg-light rounded" id="section-e">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-droplet-half me-1"></i> Section E: Intermediate Yield & Solvent Recoveries</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Stage Intermediate Output Qty</label>
                            <input type="number" step="0.001" name="output_quantity" class="form-control" value="{{ $activeExecution->output_quantity ?? 0 }}" {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Solvent Recovered (Litres)</label>
                            <input type="number" step="0.001" name="solvent_recovered_qty" class="form-control" value="{{ $activeExecution->solvent_recovered_qty ?? 0 }}" {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Solid Waste / Spent Herb (KG)</label>
                            <input type="number" step="0.001" name="spent_herb_qty" class="form-control" value="{{ $activeExecution->spent_herb_qty ?? 0 }}" {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>
                        </div>
                    </div>
                </div>

                <!-- Remarks -->
                <div class="mb-4" id="section-remarks">
                    <label class="form-label">Supervisor / Stage Remarks</label>
                    <textarea name="supervisor_remarks" rows="2" class="form-control" placeholder="Optional stage observations or notes..." {{ $isReadOnlyStage ? 'readonly disabled' : '' }}>{{ $activeExecution->supervisor_remarks }}</textarea>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top gap-2">
                    <div id="autoSaveStatus" class="small text-muted">
                        @if($isReadOnlyStage)
                            <i class="bi bi-lock me-1"></i>
                            @if($activeExecution->status === 'QC Pending')
                                Read-only: Stage submitted to QC Pending.
                            @elseif(in_array($activeExecution->status, ['Completed', 'Approved'], true))
                                Read-only: Stage is {{ $activeExecution->status }}.
                            @elseif($isActiveLocked)
                                Read-only: Stage is locked.
                            @else
                                Read-only: Stage status is {{ $activeExecution->status }}.
                            @endif
                        @else
                            <i class="bi bi-cloud-check text-success me-1"></i> Auto-save enabled
                        @endif
                    </div>

                    @if($isEditableInProgress)
                        <button type="button"
                            id="completeStageBtn"
                            class="btn btn-success">
                            <i class="bi bi-check-all me-1"></i> Complete Stage and Move to Next
                        </button>
                    @else
                        <div class="d-flex align-items-center gap-2">
                            @if($nextStageExec && in_array($activeExecution->status, ['Completed', 'Approved', 'Skipped'], true))
                                <a href="{{ route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $nextStageExec->id]) }}"
                                    class="btn btn-success">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Go to Seq {{ $nextStageExec->sequence_no }}: {{ $nextStageExec->stage_name }}
                                </a>
                            @endif
                            <button type="button"
                                id="completeStageBtn"
                                class="btn btn-secondary disabled opacity-75"
                                disabled>
                                <i class="bi bi-lock me-1"></i>
                                @if($activeExecution->status === 'QC Pending')
                                    Complete Disabled (QC Pending)
                                @elseif(in_array($activeExecution->status, ['Completed', 'Approved'], true))
                                    Stage {{ $activeExecution->status }}
                                @elseif($activeExecution->status === 'Rework Required')
                                    Rework Required (Execute New Rework)
                                @else
                                    Complete Disabled ({{ $activeExecution->status }})
                                @endif
                            </button>
                        </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('stageExecutionForm');
    if (!form) return;

    const isReadOnly = form.dataset.readonly === '1';
    const saveUrl = form.dataset.saveUrl;
    const completeUrl = form.dataset.completeUrl;
    const statusEl = document.getElementById('autoSaveStatus');
    const completeBtn = document.getElementById('completeStageBtn');
    const checklistBody = document.getElementById('stageChecklistCardBody');
    const checklistCard = document.getElementById('stageCompletionChecklistCard');

    let debounceTimer = null;
    let isSaving = false;
    let isCompleting = false;
    let pendingResave = false;

    function scrollAndHighlightSection(selector) {
        if (!selector) return;
        const target = document.querySelector(selector);
        if (!target) return;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.remove('sattva-highlight-pulse');
        void target.offsetWidth;
        target.classList.add('sattva-highlight-pulse');
        const focusable = target.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
        if (focusable) {
            setTimeout(() => focusable.focus({ preventScroll: true }), 350);
        }
    }

    document.addEventListener('click', function (e) {
        const link = e.target.closest('.goto-section-link');
        if (!link) return;
        e.preventDefault();
        scrollAndHighlightSection(link.dataset.targetAnchor || link.getAttribute('href'));
    });

    function updateFieldBadges(data) {
        if (data.parameter_statuses) {
            Object.keys(data.parameter_statuses).forEach(function (id) {
                const cell = document.getElementById('param-status-' + id);
                if (!cell) return;
                const info = data.parameter_statuses[id];
                if (info.actual_value !== null && info.actual_value !== '') {
                    if (info.is_within_range) {
                        cell.innerHTML = '<span class="badge bg-success">Within Spec</span>';
                    } else {
                        cell.innerHTML = '<span class="badge bg-danger">Out of Range (' + info.actual_value + ')</span>';
                    }
                } else {
                    cell.innerHTML = '<span class="badge bg-secondary">Pending</span>';
                }
            });
        }

        if (data.activity_statuses) {
            Object.keys(data.activity_statuses).forEach(function (id) {
                const cell = document.getElementById('activity-status-' + id);
                if (!cell) return;
                const st = data.activity_statuses[id];
                const badgeClass = st === 'Completed' ? 'badge-active' : (st === 'In Progress' ? 'badge-completed' : 'badge-pending');
                cell.innerHTML = '<span class="badge-pill ' + badgeClass + '"><span class="badge-dot"></span><span>' + st + '</span></span>';
            });
        }

        if (data.checklist_html && checklistBody) {
            checklistBody.innerHTML = data.checklist_html;
        }

        if (data.evaluation && checklistCard) {
            checklistCard.classList.toggle('border-success', !!data.evaluation.can_complete);
            checklistCard.classList.toggle('border-warning', !data.evaluation.can_complete);
        }
    }

    async function performAutoSave() {
        if (isReadOnly || isCompleting) return;
        if (isSaving) {
            pendingResave = true;
            return;
        }

        isSaving = true;
        if (statusEl) {
            statusEl.innerHTML = '<span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span> Saving...';
        }

        try {
            const formData = new FormData(form);
            const res = await fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                if (statusEl) {
                    statusEl.innerHTML = '<span class="text-warning fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i> Auto-save failed: ' + (data.message || 'Please check input') + '</span>';
                }
            } else {
                updateFieldBadges(data);
                if (statusEl) {
                    statusEl.innerHTML = '<span class="text-success"><i class="bi bi-check2-circle me-1"></i> Saved at ' + (data.saved_at || '') + '</span>';
                }
            }
        } catch (err) {
            if (statusEl) {
                statusEl.innerHTML = '<span class="text-warning fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i> Auto-save failed (network error)</span>';
            }
        } finally {
            isSaving = false;
            if (pendingResave) {
                pendingResave = false;
                performAutoSave();
            }
        }
    }

    function scheduleAutoSave() {
        if (isReadOnly || isCompleting) return;
        clearTimeout(debounceTimer);
        if (statusEl) {
            statusEl.innerHTML = '<span class="text-muted"><i class="bi bi-pencil me-1"></i> Unsaved changes...</span>';
        }
        debounceTimer = setTimeout(performAutoSave, 800);
    }

    if (!isReadOnly) {
        form.addEventListener('input', function (e) {
            if (e.target.matches('input, select, textarea')) {
                scheduleAutoSave();
            }
        });
        form.addEventListener('change', function (e) {
            if (e.target.matches('input, select, textarea')) {
                scheduleAutoSave();
            }
        });
        form.addEventListener('focusout', function (e) {
            if (e.target.matches('input, select, textarea')) {
                if (e.relatedTarget && e.relatedTarget.id === 'completeStageBtn') {
                    return;
                }
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(performAutoSave, 150);
            }
        });

        if (completeBtn) {
            completeBtn.addEventListener('click', async function () {
                if (isCompleting) return;
                clearTimeout(debounceTimer);
                isCompleting = true;
                const origHtml = completeBtn.innerHTML;
                completeBtn.disabled = true;
                completeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving & Completing...';

                try {
                    const formData = new FormData(form);
                    const res = await fetch(completeUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });
                    const data = await res.json();

                    if (res.ok && data.completed) {
                        if (window.showSattvaToast) {
                            window.showSattvaToast(data.message || 'Stage completed.', data.qc_pending ? 'warning' : 'success');
                        }
                        window.location.href = data.redirect_url || window.location.href;
                        return;
                    }

                    // Requirements not met: entered data stayed saved, refresh checklist, scroll to it, show toast
                    updateFieldBadges(data);
                    if (data.saved && statusEl) {
                        statusEl.innerHTML = '<span class="text-success"><i class="bi bi-check2-circle me-1"></i> Saved at ' + (data.saved_at || '') + '</span>';
                    }
                    if (checklistCard) {
                        checklistCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                    const msg = data.message || 'Cannot complete yet: requirements not met';
                    if (window.showSattvaToast) {
                        window.showSattvaToast(msg, 'warning');
                    }
                } catch (err) {
                    if (window.showSattvaToast) {
                        window.showSattvaToast('Failed to complete stage. Please try again.', 'error');
                    }
                } finally {
                    isCompleting = false;
                    completeBtn.disabled = false;
                    completeBtn.innerHTML = origHtml;
                }
            });
        }
    }
});
</script>
@endsection
