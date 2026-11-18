@extends('layouts.app')
@section('title', 'Cost Breakdown: ' . ($batch->batch_no ?? $batch->batch_number))
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Batch Cost Analysis: {{ $batch->batch_no ?? $batch->batch_number }}</h2>
            <p class="page-subtitle">Product: <strong>{{ $batch->product->name ?? '-' }}</strong> | Output: {{ (float)$batch->actual_qty > 0 ? $batch->actual_qty : $batch->planned_qty }} {{ $batch->uom->symbol ?? 'KG' }}</p>
        </div>
        <a href="{{ route('costing.batch-cost.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Costing</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-sattva p-4 text-center">
                <span class="text-muted small">Total Batch Manufacturing Cost</span>
                <div class="h2 fw-bold text-dark mt-2 mb-0">₹{{ number_format($cost->total_cost ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sattva p-4 text-center">
                <span class="text-muted small">Unit Production Cost (Per KG)</span>
                <div class="h2 fw-bold text-success mt-2 mb-0">₹{{ number_format($cost->cost_per_unit ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sattva p-4 text-center">
                <span class="text-muted small">Raw Material Intensity %</span>
                <div class="h2 fw-bold text-primary mt-2 mb-0">
                    {{ ($cost && $cost->total_cost > 0) ? round(($cost->raw_material_cost / $cost->total_cost) * 100, 1) : 0 }}%
                </div>
            </div>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Cost Elements Breakdown</h6></div>
        <div class="card-sattva-body p-4">
            <ul class="list-group list-group-flush">
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Botanical Raw Materials &amp; Direct Inputs Cost</span>
                    <span class="fw-bold">₹{{ number_format($cost->raw_material_cost ?? 0, 2) }}</span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Extraction Solvents &amp; Reagents Consumed</span>
                    <span class="fw-bold">₹{{ number_format($cost->solvent_cost ?? 0, 2) }}</span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Allocated Electricity Utilities</span>
                    <span class="fw-bold">₹{{ number_format($cost->electricity_cost ?? 0, 2) }}</span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Allocated Maintenance &amp; Equipment Overheads</span>
                    <span class="fw-bold">₹{{ number_format(($cost->maintenance_cost ?? 0) + ($cost->equipment_cost ?? 0), 2) }}</span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Allocated Miscellaneous &amp; Other Manufacturing Overheads</span>
                    <span class="fw-bold">₹{{ number_format(($cost->misc_cost ?? 0) + ($cost->other_cost ?? 0), 2) }}</span>
                </li>
                <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                    <span>Allocated Plant Labour</span>
                    <span class="fw-bold">₹{{ number_format($cost->labour_cost ?? 0, 2) }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection