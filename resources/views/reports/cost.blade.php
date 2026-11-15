@extends('layouts.app')
@section('title', 'Cost of Production Report')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Unit Cost of Production & Margin Analysis</h2>
            <p class="page-subtitle">Batch-wise cost allocation breakdown across raw herbs, solvents, power, and labor.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Batch No</th>
                        <th>Product Description</th>
                        <th class="text-end">Raw Herb (₹)</th>
                        <th class="text-end">Solvent (₹)</th>
                        <th class="text-end">Power & Fuel (₹)</th>
                        <th class="text-end">Labor (₹)</th>
                        <th class="text-end">Total Cost (₹)</th>
                        <th class="text-end">Cost / KG (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costs as $idx => $c)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $c->batch->batch_no ?? '-' }}</td>
                        <td class="fw-semibold text-dark">{{ $c->batch->product->name ?? '-' }}</td>
                        <td class="text-end">₹{{ number_format($c->raw_material_cost, 2) }}</td>
                        <td class="text-end">₹{{ number_format($c->solvent_cost, 2) }}</td>
                        <td class="text-end">₹{{ number_format($c->utility_cost, 2) }}</td>
                        <td class="text-end">₹{{ number_format($c->labor_cost, 2) }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($c->total_cost, 2) }}</td>
                        <td class="text-end fw-bold text-success fs-6">₹{{ number_format($c->cost_per_unit, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No cost data available.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection