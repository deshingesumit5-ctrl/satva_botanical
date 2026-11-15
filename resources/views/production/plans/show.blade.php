@extends('layouts.app')
@section('title', 'Plan: ' . $plan->plan_no)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $plan->plan_no }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $plan->status)) }}">{{ $plan->status }}</span>
            </div>
            <p class="page-subtitle">Product: <strong>{{ $plan->product->name ?? '-' }}</strong> | Recipe: {{ $plan->recipe->name ?? '-' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.plans.requirements', $plan) }}" class="btn btn-info text-white"><i class="bi bi-clipboard-check me-1"></i> Material Requirements</a>
            <a href="{{ route('production.batches.create', ['plan_id' => $plan->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-plus-lg me-1"></i> Issue Batch from Plan</a>
            <a href="{{ route('production.plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Plan Campaign Details</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="text-muted small">Planned Total Quantity</label><div class="fw-bold fs-5 text-success">{{ $plan->planned_quantity }} {{ $plan->uom->symbol ?? 'KG' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Plant Facility</label><div class="fw-bold">{{ $plan->plant->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Start Date</label><div class="fw-bold">{{ $plan->start_date ? \Carbon\Carbon::parse($plan->start_date)->format('d-m-Y') : '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">End Date</label><div class="fw-bold">{{ $plan->end_date ? \Carbon\Carbon::parse($plan->end_date)->format('d-m-Y') : '-' }}</div></div>
            </div>

            <h6 class="fw-bold mb-3">Issued Production Batches</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Batch No</th>
                            <th>Batch Size</th>
                            <th>Start Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plan->batches as $b)
                        <tr>
                            <td class="fw-bold font-monospace text-success">{{ $b->batch_no ?? $b->batch_number }}</td>
                            <td>{{ $b->planned_quantity ?? $b->batch_size }} {{ $plan->uom->symbol ?? 'KG' }}</td>
                            <td>{{ $b->start_date ? \Carbon\Carbon::parse($b->start_date)->format('d-m-Y') : '-' }}</td>
                            <td><span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $b->status)) }}">{{ $b->status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('production.execution.show', $b) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-play-circle me-1"></i> Execute</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">No batches issued under this plan yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection