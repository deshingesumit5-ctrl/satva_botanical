@extends('layouts.app')
@section('title', 'QC Inspection History')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h2 class="page-title">QC History & Full Audit Trail</h2>
            <p class="page-subtitle">Original QC submissions, readings, manager decisions, rework instructions, retest results, and live pending requests.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted small me-2">
                <i class="bi bi-clock me-1"></i>Last updated: <strong id="qcHistoryLastUpdated">{{ $lastUpdated }}</strong>
            </span>
            <button type="button" id="refreshQcHistoryBtn" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
            <a href="{{ route('qc.pending') }}" class="btn btn-sm btn-warning">
                <i class="bi bi-hourglass-split me-1"></i> Pending Inspections
            </a>
            <a href="{{ route('qc.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> QC Dashboard
            </a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('qc.history') }}" method="GET" class="row g-2 align-items-center" id="qcHistoryFilterForm">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search request #, batch #, product, stage...">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                        <option value="Rework Required" {{ request('status') === 'Rework Required' ? 'selected' : '' }}>Rework Required</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="product_id" class="form-select">
                        <option value="">All Products</option>
                        @foreach($products ?? [] as $p)
                            <option value="{{ $p->id }}" {{ (string) request('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control" title="From Date"></div>
                <div class="col-md-1"><input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control" title="To Date"></div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('qc.history') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-sattva" id="qcHistoryTableContainer">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Request Number</th>
                        <th>Batch / Product / Stage</th>
                        <th>Original Submission & Readings</th>
                        <th>Decision & QC Manager</th>
                        <th>Rework & Retest Result</th>
                        <th>Current Status</th>
                        <th width="90" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $idx => $req)
                    @php
                        $snapshotParams = $req->readings_snapshot['parameters'] ?? [];
                        if (empty($snapshotParams) && $req->stageExecution) {
                            $snapshotParams = $req->stageExecution->parameterReadings->map(fn($r) => [
                                'parameter_name' => $r->parameter_name,
                                'actual_value' => $r->actual_value,
                                'uom' => $r->uom,
                            ])->toArray();
                        }
                    @endphp
                    <tr>
                        <td>{{ $requests->firstItem() + $idx }}</td>
                        <td>
                            <a href="{{ route('qc.show', $req) }}" class="fw-bold font-monospace text-success text-decoration-none">{{ $req->qc_request_number }}</a>
                            @if($req->parentRequest)
                                <div class="small text-muted">Retest of <a href="{{ route('qc.show', $req->parentRequest) }}" class="text-decoration-none">{{ $req->parentRequest->qc_request_number }}</a></div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $req->product?->name ?? $req->batch?->product?->name ?? '-' }}</div>
                            <div class="small font-monospace text-muted">Batch: {{ $req->batch?->batch_number ?? '-' }}</div>
                            <div class="small text-muted">
                                Stage: {{ $req->stage_name ?? $req->stageExecution?->stage_name ?? '-' }}
                                <span class="badge bg-light text-dark border ms-1">#{{ $req->stageExecution?->execution_number ?? 1 }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="small"><strong>Date:</strong> {{ $req->request_date ? $req->request_date->format('d-m-Y H:i') : ($req->created_at ? $req->created_at->format('d-m-Y H:i') : '-') }}</div>
                            <div class="small"><strong>Submitted By:</strong> {{ $req->submittedByUser?->name ?? '-' }}</div>
                            @if(!empty($snapshotParams))
                                <div class="small text-muted mt-1">
                                    <strong>Readings:</strong>
                                    @foreach(array_slice($snapshotParams, 0, 3) as $sp)
                                        <span class="badge bg-light text-dark border me-1">{{ $sp['parameter_name'] ?? '' }}: {{ $sp['actual_value'] ?? 'N/A' }} {{ $sp['uom'] ?? '' }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if($req->results->isNotEmpty())
                                <div class="small text-muted mt-1">
                                    <strong>QC Tests:</strong>
                                    @foreach($req->results->take(2) as $res)
                                        <span class="badge bg-light text-dark border me-1">{{ $res->test_name }}: {{ $res->actual_value ?? 'Pending' }} ({{ $res->result_status }})</span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($req->decision_at)
                                <div class="small"><strong>QC Manager:</strong> {{ $req->decisionByUser?->name ?? '-' }}</div>
                                <div class="small"><strong>At:</strong> {{ $req->decision_at->format('d-m-Y H:i') }}</div>
                                @if($req->decision_remarks)
                                    <div class="small text-muted"><strong>Reason/Remarks:</strong> {{ $req->decision_remarks }}</div>
                                @endif
                            @else
                                <span class="text-muted small">Awaiting QC Manager decision</span>
                            @endif
                        </td>
                        <td>
                            @if($req->rework_instructions)
                                <div class="small mb-1"><strong>Instruction:</strong> {{ $req->rework_instructions }}</div>
                            @endif
                            @if($req->retestRequest)
                                <div class="small">
                                    <strong>Retest:</strong>
                                    <a href="{{ route('qc.show', $req->retestRequest) }}" class="font-monospace text-decoration-none">{{ $req->retestRequest->qc_request_number }}</a>
                                    <span class="ms-1"><x-status-badge :status="$req->retestRequest->status" /></span>
                                    @if($req->retestRequest->decision_remarks)
                                        <div class="text-muted">{{ $req->retestRequest->decision_remarks }}</div>
                                    @endif
                                </div>
                            @elseif(!$req->rework_instructions)
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td><x-status-badge :status="$req->status" /></td>
                        <td class="text-end">
                            <a href="{{ route('qc.show', $req) }}" class="btn btn-sm btn-outline-secondary" title="View QC Request">
                                <i class="bi bi-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No QC records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $requests->firstItem() }} to {{ $requests->lastItem() }} of {{ $requests->total() }}</span>
            {{ $requests->links() }}
        </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const refreshBtn = document.getElementById('refreshQcHistoryBtn');
    const lastUpdatedEl = document.getElementById('qcHistoryLastUpdated');
    const containerEl = document.getElementById('qcHistoryTableContainer');

    async function refreshQcHistory() {
        try {
            const url = window.location.href;
            const res = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });
            if (!res.ok) return;
            const html = await res.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContainer = doc.getElementById('qcHistoryTableContainer');
            const newLastUpdated = doc.getElementById('qcHistoryLastUpdated');
            if (newContainer && containerEl) {
                containerEl.innerHTML = newContainer.innerHTML;
            }
            if (newLastUpdated && lastUpdatedEl) {
                lastUpdatedEl.textContent = newLastUpdated.textContent;
            }
        } catch (e) {
            // Silent retry on next interval
        }
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshQcHistory);
    }

    setInterval(refreshQcHistory, 30000);
});
</script>
@endsection
