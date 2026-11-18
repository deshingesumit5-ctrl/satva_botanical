@extends('layouts.app')
@section('title', 'Pending QC Inspections')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Pending QC Inspections</h2>
                <p class="page-subtitle">In-process production stage inspections awaiting QC Manager review and sign-off.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('qc.history') }}" class="btn btn-outline-secondary"><i
                        class="bi bi-clock-history me-1"></i> QC History</a>
                <a href="{{ route('qc.dashboard') }}" class="btn btn-outline-secondary"><i
                        class="bi bi-arrow-left me-1"></i> QC Dashboard</a>
            </div>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('qc.pending') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search request no, batch no, product, stage..."></div>
                    <div class="col-md-2">
                        <select name="product_id" class="form-select">
                            <option value="">All Products</option>
                            @foreach($products ?? [] as $p)
                                <option value="{{ $p->id }}" {{ (string) request('product_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><input type="date" name="from_date" value="{{ request('from_date') }}"
                            class="form-control" title="From Date"></div>
                    <div class="col-md-2"><input type="date" name="to_date" value="{{ request('to_date') }}"
                            class="form-control" title="To Date"></div>
                    <div class="col-md-2 d-flex gap-2">
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
                            <th>QC Request Number</th>
                            <th>Batch Number</th>
                            <th>Product</th>
                            <th>Stage</th>
                            <th>Exec #</th>
                            <th>Request Date</th>
                            <th>Submitted By</th>
                            <th>Status</th>
                            <th width="140" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $idx => $req)
                            <tr>
                                <td>{{ $requests->firstItem() + $idx }}</td>
                                <td class="fw-bold font-monospace text-success">
                                    <a href="{{ route('qc.show', $req) }}"
                                        class="text-decoration-none text-success">{{ $req->qc_request_number }}</a>
                                </td>
                                <td class="font-monospace fw-semibold">{{ $req->batch?->batch_number ?? '-' }}</td>
                                <td class="fw-semibold text-dark">
                                    {{ $req->product?->name ?? $req->batch?->product?->name ?? '-' }}</td>
                                <td>{{ $req->stage_name ?? $req->stageExecution?->stage_name ?? $req->stageExecution?->stage?->name ?? '-' }}
                                </td>
                                <td><span
                                        class="badge bg-light text-dark border">#{{ $req->stageExecution?->execution_number ?? 1 }}</span>
                                </td>
                                <td>{{ $req->request_date ? $req->request_date->format('d-m-Y H:i') : ($req->created_at ? $req->created_at->format('d-m-Y H:i') : '-') }}
                                </td>
                                <td>{{ $req->submittedByUser?->name ?? 'Plant' }}</td>
                                <td><x-status-badge :status="$req->status" /></td>
                                <td class="text-end">
                                    <a href="{{ route('qc.show', $req) }}" class="btn btn-sm btn-primary-sattva"><i
                                            class="bi bi-clipboard-pulse me-1"></i> Inspect</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No pending QC requests.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $requests->firstItem() }} to {{ $requests->lastItem() }} of
                        {{ $requests->total() }}</span>
                    {{ $requests->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection