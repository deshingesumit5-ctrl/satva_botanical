@extends('layouts.app')
@section('title', 'QC Inspection: ' . $qcRequest->qc_request_number)
@section('content')
@php
    $snapshotParams = $qcRequest->readings_snapshot['parameters'] ?? [];
    if (empty($snapshotParams) && $qcRequest->stageExecution) {
        $snapshotParams = $qcRequest->stageExecution->parameterReadings->map(fn($r) => [
            'parameter_name' => $r->parameter_name,
            'target_value' => $r->target_value,
            'min_value' => $r->min_value,
            'max_value' => $r->max_value,
            'actual_value' => $r->actual_value,
            'uom' => $r->uom,
            'is_within_range' => (bool) $r->is_within_range,
        ])->toArray();
    }

    $snapshotOutputs = $qcRequest->outputs_snapshot['outputs'] ?? [];
    if (empty($snapshotOutputs) && $qcRequest->stageExecution) {
        $snapshotOutputs = $qcRequest->stageExecution->outputs->map(fn($o) => [
            'output_type' => $o->output_type,
            'output_name' => $o->output_name,
            'actual_qty' => (string) $o->actual_qty,
            'uom' => $o->uom?->symbol ?? $o->uom?->uom_code ?? '',
        ])->toArray();
    }
@endphp
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $qcRequest->qc_request_number }}</h2>
                <x-status-badge :status="$qcRequest->status" />
            </div>
            <p class="page-subtitle">
                Submitted on: {{ $qcRequest->request_date ? $qcRequest->request_date->format('d-m-Y H:i') : ($qcRequest->created_at ? $qcRequest->created_at->format('d-m-Y H:i') : '-') }}
                by <strong>{{ $qcRequest->submittedByUser?->name ?? 'Production' }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($qcRequest->batch)
                <a href="{{ route('production.execution.show', ['batch' => $qcRequest->batch_id, 'stage_execution_id' => $qcRequest->batch_stage_execution_id]) }}" class="btn btn-outline-primary">
                    <i class="bi bi-play-circle me-1"></i> Open Stage Execution
                </a>
            @endif
            <a href="{{ route('qc.history') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i> QC History</a>
            <a href="{{ route('qc.pending') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Pending</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Sample Context Information</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="text-muted small">Product</label>
                    <div class="fw-bold">{{ $qcRequest->product?->name ?? $qcRequest->batch?->product?->name ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Batch Reference</label>
                    <div class="fw-bold font-monospace text-success">
                        @if($qcRequest->batch)
                            <a href="{{ route('production.batches.show', $qcRequest->batch) }}" class="text-decoration-none text-success">{{ $qcRequest->batch->batch_number }}</a>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="text-muted small">Recipe Version</label>
                    <div class="fw-bold">v{{ $qcRequest->recipeVersion?->version_number ?? $qcRequest->batch?->recipeVersion?->version_number ?? '-' }}</div>
                </div>
                <div class="col-md-2">
                    <label class="text-muted small">Process Stage</label>
                    <div class="fw-bold">{{ $qcRequest->stage_name ?? $qcRequest->stageExecution?->stage_name ?? '-' }} (#{{ $qcRequest->stageExecution?->execution_number ?? 1 }})</div>
                </div>
                <div class="col-md-2">
                    <label class="text-muted small">Submitted By</label>
                    <div class="fw-bold">{{ $qcRequest->submittedByUser?->name ?? 'Plant' }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Stage Readings & Outputs Snapshot -->
    <div class="row g-4 mb-4">
        <div class="col-md-7">
            <div class="card card-sattva h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Stage Parameter Readings Snapshot</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Parameter</th>
                                <th>Target / Range</th>
                                <th>Actual Reading</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($snapshotParams as $sp)
                            <tr>
                                <td class="fw-semibold">{{ $sp['parameter_name'] ?? '-' }}</td>
                                <td>{{ $sp['target_value'] ?? '-' }} ({{ $sp['min_value'] ?? '*' }} - {{ $sp['max_value'] ?? '*' }} {{ $sp['uom'] ?? '' }})</td>
                                <td class="fw-bold">{{ $sp['actual_value'] ?? 'Not entered' }} {{ $sp['uom'] ?? '' }}</td>
                                <td>
                                    @if(($sp['actual_value'] ?? null) === null || $sp['actual_value'] === '')
                                        <span class="badge bg-secondary">Not Entered</span>
                                    @elseif(!empty($sp['is_within_range']))
                                        <span class="badge bg-success">Within Spec</span>
                                    @else
                                        <span class="badge bg-danger">Out of Range</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-3 text-muted">No process parameter readings recorded for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card card-sattva h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Stage Outputs Snapshot</h6></div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Output</th>
                                <th>Actual Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($snapshotOutputs as $so)
                            <tr>
                                <td><span class="badge bg-light text-dark border">{{ $so['output_type'] ?? '-' }}</span></td>
                                <td class="fw-semibold">{{ $so['output_name'] ?? '-' }}</td>
                                <td class="fw-bold">{{ $so['actual_qty'] ?? 0 }} {{ $so['uom'] ?? '' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-3 text-muted">No stage outputs recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- QC Test Parameters & Decisions -->
    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">QC Test Parameters & Review Decision</h6></div>
        <div class="card-sattva-body p-4">
            <form action="{{ route('qc.save-results', $qcRequest) }}" method="POST">
                @csrf
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>QC Test Name</th>
                                <th>Standard Specification</th>
                                <th>Test Method</th>
                                <th width="220">Observed Value</th>
                                <th>Compliance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($qcRequest->results as $res)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $res->test_name ?? $res->qcTest?->name ?? 'Test' }}</div>
                                    <small class="text-muted">{{ $res->qcTest?->test_type ?? '' }}</small>
                                </td>
                                <td>
                                    @if($res->expected_min !== null || $res->expected_max !== null)
                                        {{ $res->expected_min ?? 'Min' }} to {{ $res->expected_max ?? 'Max' }} {{ $res->uom ?? '' }}
                                    @elseif($res->expected_value !== null)
                                        Target: {{ $res->expected_value }} {{ $res->uom ?? '' }}
                                    @else
                                        {{ $res->qcTest?->standard_specification ?? 'Conforms' }}
                                    @endif
                                </td>
                                <td>{{ $res->test_method ?? $res->qcTest?->test_method ?? 'In-House' }}</td>
                                <td>
                                    <input type="text" name="results[{{ $res->id }}][actual_value]" class="form-control form-control-sm" value="{{ $res->actual_value }}" placeholder="Observed reading" {{ $qcRequest->status !== 'Pending' ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    @if($res->actual_value !== null && $res->actual_value !== '' && $res->result_status === 'Pass')
                                        <span class="badge bg-success">Passed</span>
                                    @elseif($res->actual_value !== null && $res->actual_value !== '' && $res->result_status === 'Fail')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">Awaiting Entry</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-3 text-muted">No QC test lines mapped in Recipe QC Mapping; review the stage readings and outputs above before approving or requesting rework.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($qcRequest->status === 'Pending')
                <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top gap-2">
                    @if($qcRequest->results->isNotEmpty())
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i> Save Test Readings</button>
                    @else
                        <div></div>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#qcApproveModal">
                            <i class="bi bi-check-circle me-1"></i> Approve & Release
                        </button>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#qcReworkModal">
                            <i class="bi bi-arrow-repeat me-1"></i> Rework Required
                        </button>
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#qcRejectModal">
                            <i class="bi bi-x-circle me-1"></i> Reject Sample
                        </button>
                    </div>
                </div>
                @endif
            </form>
        </div>
    </div>

    @if($qcRequest->decision_at || $qcRequest->parentRequest || $qcRequest->retestRequest)
    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">QC Decision & Retest Audit Trail</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="text-muted small">Decision Status</label>
                    <div><x-status-badge :status="$qcRequest->status" /></div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">QC Manager</label>
                    <div class="fw-bold">{{ $qcRequest->decisionByUser?->name ?? '-' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Decision Timestamp</label>
                    <div class="fw-bold">{{ $qcRequest->decision_at ? $qcRequest->decision_at->format('d-m-Y H:i') : '-' }}</div>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Reason / Remarks</label>
                    <div class="fw-semibold">{{ $qcRequest->decision_remarks ?? '-' }}</div>
                </div>
                @if($qcRequest->rework_instructions)
                <div class="col-md-6">
                    <label class="text-muted small">Rework Instructions</label>
                    <div class="fw-semibold text-warning-emphasis">{{ $qcRequest->rework_instructions }}</div>
                </div>
                @endif
                @if($qcRequest->parentRequest)
                <div class="col-md-6">
                    <label class="text-muted small">Original QC Request (Before Rework)</label>
                    <div><a href="{{ route('qc.show', $qcRequest->parentRequest) }}" class="fw-bold font-monospace">{{ $qcRequest->parentRequest->qc_request_number }}</a></div>
                </div>
                @endif
                @if($qcRequest->retestRequest)
                <div class="col-md-6">
                    <label class="text-muted small">Retest QC Request</label>
                    <div>
                        <a href="{{ route('qc.show', $qcRequest->retestRequest) }}" class="fw-bold font-monospace">{{ $qcRequest->retestRequest->qc_request_number }}</a>
                        <span class="ms-2"><x-status-badge :status="$qcRequest->retestRequest->status" /></span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

@if($qcRequest->status === 'Pending')
<!-- Approve Modal -->
<div class="modal fade" id="qcApproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('qc.approve', $qcRequest) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Approve QC Request {{ $qcRequest->qc_request_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Approving this QC inspection will unlock the next production stage automatically.</p>
                <label class="form-label">Approval Remarks (Optional)</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="All specs within acceptable range..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Confirm Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- Rework Modal -->
<div class="modal fade" id="qcReworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('qc.rework', $qcRequest) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Request Rework for {{ $qcRequest->qc_request_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Rework Reason</label>
                    <input type="text" name="reason" class="form-control" required placeholder="Why rework is needed...">
                </div>
                <div class="mb-3">
                    <label class="form-label required">Rework Instructions</label>
                    <textarea name="instructions" class="form-control" rows="3" required placeholder="Step-by-step instructions for rework execution..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-repeat me-1"></i> Send for Rework</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="qcRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('qc.reject', $qcRequest) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title text-danger">Reject QC Request {{ $qcRequest->qc_request_number }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-danger fw-semibold">Rejecting this QC request will mark the entire manufacturing batch as Rejected and stop further normal production.</p>
                <label class="form-label required">Rejection Reason</label>
                <textarea name="reason" class="form-control" rows="3" required placeholder="Enter OOS / rejection reason..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="bi bi-x-circle me-1"></i> Confirm Reject</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
