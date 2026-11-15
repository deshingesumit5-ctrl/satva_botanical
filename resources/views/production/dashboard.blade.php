@extends('layouts.app')
@section('title', 'Production Dashboard')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Herbal Extraction & Production Dashboard</h2>
            <p class="page-subtitle">Batch manufacturing execution, stage progress, yield tracking and quality clearance.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.plans.create') }}" class="btn btn-outline-secondary"><i class="bi bi-calendar-plus me-1"></i> New Plan</a>
            <a href="{{ route('production.batches.create') }}" class="btn btn-primary-sattva"><i class="bi bi-plus-lg me-1"></i> Issue New Batch</a>
        </div>
    </div>

    <!-- Quick Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-primary">
                <span class="text-muted small">Active Plans</span>
                <div class="h3 fw-bold text-dark mb-0 mt-1">{{ $plans->where('status', 'Active')->count() ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-success">
                <span class="text-muted small">Batches In Progress</span>
                <div class="h3 fw-bold text-success mb-0 mt-1">{{ $activeBatchesCount ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-warning">
                <span class="text-muted small">Batches On Hold</span>
                <div class="h3 fw-bold text-warning mb-0 mt-1">{{ $onHoldCount ?? 0 }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-info">
                <span class="text-muted small">Completed Batches</span>
                <div class="h3 fw-bold text-info mb-0 mt-1">{{ $completedBatchesCount ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Live Production Batches</h6>
            <a href="{{ route('production.batches.index') }}" class="btn btn-sm btn-outline-secondary">View All Batches</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Batch Number</th>
                        <th>Target Product</th>
                        <th>Batch Size</th>
                        <th>Current Stage</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th width="140" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentBatches as $idx => $b)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $b->batch_no ?? $b->batch_number }}</td>
                        <td class="fw-semibold text-dark">{{ $b->product->name ?? '-' }}</td>
                        <td>{{ $b->planned_quantity ?? $b->batch_size }} {{ $b->uom->symbol ?? 'KG' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $b->currentStage->name ?? 'Stage 1' }}</span></td>
                        <td>{{ $b->start_date ? \Carbon\Carbon::parse($b->start_date)->format('d-m-Y') : '-' }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $b->status)) }}">{{ $b->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('production.execution.show', $b) }}" class="btn btn-sm btn-outline-primary me-1" title="Execution Flow"><i class="bi bi-play-circle me-1"></i> Execute</a>
                            <a href="{{ route('production.batches.show', $b) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No live batches running.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection