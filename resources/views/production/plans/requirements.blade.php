@extends('layouts.app')
@section('title', 'Material Requirements - Plan: ' . $plan->plan_no)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Material Requirement Planning (MRP)</h2>
            <p class="page-subtitle">Plan: <strong>{{ $plan->plan_no }}</strong> | Product: {{ $plan->product->name ?? '-' }} (Planned: {{ $plan->planned_quantity }} {{ $plan->uom->symbol ?? 'KG' }})</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.plans.show', $plan) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Plan</a>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Required Raw Materials, Solvents & Inventory Readiness</h6></div>
        <div class="card-sattva-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Material Description</th>
                            <th>Category</th>
                            <th>Required Qty</th>
                            <th>Available In Stock</th>
                            <th>Shortfall</th>
                            <th>Stock Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plan->requirements as $idx => $req)
                        @php
                            $shortfall = max(0, $req->required_qty - ($req->available_qty ?? 0));
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-semibold text-dark">{{ $req->material->name ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $req->material->material_type ?? 'RM' }}</span></td>
                            <td class="fw-bold">{{ $req->required_qty }} {{ $req->uom->symbol ?? '' }}</td>
                            <td>{{ $req->available_qty ?? 0 }} {{ $req->uom->symbol ?? '' }}</td>
                            <td class="{{ $shortfall > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                {{ $shortfall > 0 ? $shortfall : 'Nil' }}
                            </td>
                            <td>
                                @if($shortfall > 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-exclamation-triangle me-1"></i>Shortage</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Available</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($shortfall > 0)
                                    <a href="{{ route('purchase.requisitions.create') }}" class="btn btn-sm btn-outline-danger" title="Create Purchase Indent"><i class="bi bi-cart-plus me-1"></i> Indent</a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">No requirements calculated. Ensure the recipe has BOM materials defined.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection