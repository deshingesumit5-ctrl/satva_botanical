@extends('layouts.app')
@section('title', 'Production Plans')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Production Plans & Schedules</h2>
            <p class="page-subtitle">Monthly and campaign production schedules, material requirement calculations (MRP).</p>
        </div>
        <a href="{{ route('production.plans.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> New Production Plan
        </a>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Plan Number</th>
                        <th>Product</th>
                        <th>Planned Output</th>
                        <th>Target Batches</th>
                        <th>Schedule Dates</th>
                        <th>Status</th>
                        <th width="160" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $idx => $plan)
                    <tr>
                        <td>{{ $plans->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $plan->plan_no }}</td>
                        <td class="fw-semibold text-dark">{{ $plan->product->name ?? '-' }}</td>
                        <td>{{ $plan->planned_quantity }} {{ $plan->uom->symbol ?? 'KG' }}</td>
                        <td>{{ $plan->number_of_batches ?? $plan->batches()->count() }} Batches</td>
                        <td>
                            {{ $plan->start_date ? \Carbon\Carbon::parse($plan->start_date)->format('d-m-Y') : '-' }} to
                            {{ $plan->end_date ? \Carbon\Carbon::parse($plan->end_date)->format('d-m-Y') : '-' }}
                        </td>
                        <td><span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $plan->status)) }}">{{ $plan->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('production.plans.requirements', $plan) }}" class="btn btn-sm btn-icon btn-outline-info me-1" title="Material Requirements"><i class="bi bi-clipboard-check"></i></a>
                            <a href="{{ route('production.plans.show', $plan) }}" class="btn btn-sm btn-icon btn-outline-secondary me-1" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('production.plans.edit', $plan) }}" class="btn btn-sm btn-icon btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No production plans scheduled.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($plans->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $plans->firstItem() }} to {{ $plans->lastItem() }} of {{ $plans->total() }}</span>
            {{ $plans->links() }}
        </div>
        @endif
    </div>
</div>
@endsection