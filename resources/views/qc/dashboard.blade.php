@extends('layouts.app')
@section('title', 'Quality Control (QC) Dashboard')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Quality Control & Laboratory Testing</h2>
                <p class="page-subtitle">Assay analysis, botanical testing, in-process stage inspection, and Certificate of
                    Analysis (CoA).</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('qc.pending') }}" class="btn btn-warning"><i class="bi bi-hourglass-split me-1"></i>
                    Pending Samples ({{ $pendingCount }})</a>
                <a href="{{ route('qc.history') }}" class="btn btn-outline-secondary"><i
                        class="bi bi-clock-history me-1"></i> QC History</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card card-sattva p-3 border-start border-4 border-warning">
                    <span class="text-muted small">Pending Inspection</span>
                    <div class="h3 fw-bold text-warning mb-0 mt-1">{{ $pendingCount }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-sattva p-3 border-start border-4 border-success">
                    <span class="text-muted small">Approved Samples</span>
                    <div class="h3 fw-bold text-success mb-0 mt-1">{{ $approvedCount }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-sattva p-3 border-start border-4 border-danger">
                    <span class="text-muted small">Rejected / OOS</span>
                    <div class="h3 fw-bold text-danger mb-0 mt-1">{{ $rejectedCount }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-sattva p-3 border-start border-4 border-info">
                    <span class="text-muted small">Sent for Rework</span>
                    <div class="h3 fw-bold text-info mb-0 mt-1">{{ $reworkCount }}</div>
                </div>
            </div>
        </div>

        <div class="card card-sattva">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Recent Laboratory QC Test Requests</h6>
                <a href="{{ route('qc.pending') }}" class="btn btn-sm btn-outline-secondary">View Pending</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Request Number</th>
                            <th>Stage</th>
                            <th>Product</th>
                            <th>Batch Number</th>
                            <th>Submitted By</th>
                            <th>Request Date</th>
                            <th>Status</th>
                            <th width="120" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRequests as $idx => $req)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="fw-bold font-monospace text-success">
                                    <a href="{{ route('qc.show', $req) }}"
                                        class="text-decoration-none text-success">{{ $req->qc_request_number }}</a>
                                </td>
                                <td><span
                                        class="badge bg-light text-dark border">{{ $req->stage_name ?? $req->stageExecution?->stage_name ?? 'In-Process' }}</span>
                                </td>
                                <td class="fw-semibold text-dark">
                                    {{ $req->product?->name ?? $req->batch?->product?->name ?? '-' }}
                                </td>
                                <td class="font-monospace">{{ $req->batch?->batch_number ?? '-' }}</td>
                                <td>{{ $req->submittedByUser?->name ?? 'Plant' }}</td>
                                <td>{{ $req->request_date ? $req->request_date->format('d-m-Y H:i') : ($req->created_at ? $req->created_at->format('d-m-Y H:i') : '-') }}
                                </td>
                                <td><x-status-badge :status="$req->status" /></td>
                                <td class="text-end">
                                    <a href="{{ route('qc.show', $req) }}" class="btn btn-sm btn-outline-primary"><i
                                            class="bi bi-pencil-square me-1"></i> Inspect</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No recent QC requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection