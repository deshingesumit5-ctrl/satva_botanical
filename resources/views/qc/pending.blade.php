@extends('layouts.app')
@section('title', 'Pending QC Inspections')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Pending QC Samples & Hold Points</h2>
            <p class="page-subtitle">Raw material GRN quarantine lots and in-process production stage inspections awaiting lab sign-off.</p>
        </div>
        <a href="{{ route('qc.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> QC Dashboard</a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('qc.pending') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-9"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search request number..."></div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('qc.pending') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Request Number</th>
                        <th>Sample Type</th>
                        <th>Material / Batch</th>
                        <th>Stage Reference</th>
                        <th>Submitted By</th>
                        <th>Submitted At</th>
                        <th width="140" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $idx => $req)
                    <tr>
                        <td>{{ $requests->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $req->qc_request_number }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $req->sample_type }}</span></td>
                        <td class="fw-semibold text-dark">{{ $req->material->name ?? $req->batch->product->name ?? '-' }}</td>
                        <td>{{ $req->stageExecution->stage->name ?? '-' }}</td>
                        <td>{{ $req->submittedByUser->name ?? 'Plant' }}</td>
                        <td>{{ $req->created_at->format('d-m-Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('qc.show', $req) }}" class="btn btn-sm btn-primary-sattva"><i class="bi bi-clipboard-pulse me-1"></i> Enter Results</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No pending QC requests.</td></tr>
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