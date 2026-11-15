@extends('layouts.app')
@section('title', 'QC Inspection History')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">QC Inspection History & CoA Archive</h2>
            <p class="page-subtitle">Historical laboratory test certificates, release audits and out-of-specification logs.</p>
        </div>
        <a href="{{ route('qc.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to QC Dashboard</a>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Request Number</th>
                        <th>Type</th>
                        <th>Item Tested</th>
                        <th>Batch / Lot</th>
                        <th>Inspected By</th>
                        <th>Decision Date</th>
                        <th>Status</th>
                        <th width="100" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $idx => $req)
                    <tr>
                        <td>{{ $requests->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $req->qc_request_number }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $req->sample_type ?? 'In-Process' }}</span></td>
                        <td class="fw-semibold text-dark">{{ $req->batch->product->name ?? '-' }}</td>
                        <td>{{ $req->batch->batch_no ?? $req->batch->batch_number ?? '-' }}</td>
                        <td>{{ $req->decisionByUser->name ?? $req->approvedByUser->name ?? '-' }}</td>
                        <td>{{ $req->decision_at ? \Carbon\Carbon::parse($req->decision_at)->format('d-m-Y H:i') : ($req->created_at ? $req->created_at->format('d-m-Y H:i') : '-') }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($req->status) }}">{{ $req->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('qc.show', $req) }}" class="btn btn-sm btn-icon btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No historical QC records found.</td></tr>
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
@endsection