@extends('layouts.app')
@section('title', 'QC Inspection: ' . $qcRequest->qc_request_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $qcRequest->qc_request_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower($qcRequest->status) }}">{{ $qcRequest->status }}</span>
            </div>
            <p class="page-subtitle">Sample Type: <strong>{{ $qcRequest->sample_type ?? 'In-Process' }}</strong> | Submitted on: {{ $qcRequest->created_at ? $qcRequest->created_at->format('d-m-Y H:i') : ($qcRequest->request_date ? \Carbon\Carbon::parse($qcRequest->request_date)->format('d-m-Y H:i') : '-') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('qc.pending') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Pending</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Sample Context Information</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><label class="text-muted small">Tested Item</label><div class="fw-bold">{{ $qcRequest->material->name ?? $qcRequest->batch->product->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Batch / Lot Reference</label><div class="fw-bold font-monospace text-success">{{ $qcRequest->batch->batch_no ?? $qcRequest->batch->batch_number ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Process Stage</label><div class="fw-bold">{{ $qcRequest->stageExecution->stage->name ?? 'Incoming Quarantine' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Sampling Officer</label><div class="fw-bold">{{ $qcRequest->submittedByUser->name ?? 'QC Inspector' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Test Parameters & Specification Limits</h6></div>
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
                                <th width="220">Observed Value *</th>
                                <th>Compliance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($qcRequest->results as $res)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $res->qcTest->name ?? 'Test' }}</div>
                                    <small class="text-muted">{{ $res->qcTest->test_type ?? '' }}</small>
                                </td>
                                <td>
                                    @if($res->standard_min !== null || $res->standard_max !== null)
                                        {{ $res->standard_min ?? 'Min' }} to {{ $res->standard_max ?? 'Max' }}
                                    @else
                                        {{ $res->qcTest->standard_specification ?? 'Conforms' }}
                                    @endif
                                </td>
                                <td>{{ $res->qcTest->test_method ?? 'In-House' }}</td>
                                <td>
                                    <input type="text" name="results[{{ $res->id }}][actual_value]" class="form-control form-control-sm" value="{{ $res->actual_value }}" placeholder="Observed reading" {{ $qcRequest->status !== 'Pending' ? 'disabled' : '' }}>
                                </td>
                                <td>
                                    @if($res->is_passed === true)
                                        <span class="badge bg-success">Passed</span>
                                    @elseif($res->is_passed === false)
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">Awaiting Entry</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-3 text-muted">No individual test lines attached.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($qcRequest->status === 'Pending')
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i> Save Test Readings</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="confirmAction('Approve this QC inspection and release lot/stage?', document.getElementById('approveQcForm'))">
                            <i class="bi bi-check-circle me-1"></i> Approve & Release
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmAction('Reject this QC inspection (OOS)?', document.getElementById('rejectQcForm'))">
                            <i class="bi bi-x-circle me-1"></i> Reject Sample
                        </button>
                    </div>
                </div>
                @endif
            </form>

            <form id="approveQcForm" action="{{ route('qc.approve', $qcRequest) }}" method="POST" class="d-none">@csrf</form>
            <form id="rejectQcForm" action="{{ route('qc.reject', $qcRequest) }}" method="POST" class="d-none">@csrf</form>
        </div>
    </div>
</div>
@endsection