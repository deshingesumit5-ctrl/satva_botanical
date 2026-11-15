@extends('layouts.app')
@section('title', 'Batch Costing Analysis')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Unit Cost of Production & Batch Costing</h2>
            <p class="page-subtitle">Comprehensive breakdown of raw materials, solvents, energy, and labor costs per kilogram of finished botanical extract.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Batch Number</th>
                        <th>Finished Good Product</th>
                        <th class="text-end">RM Cost (₹)</th>
                        <th class="text-end">Solvent Cost (₹)</th>
                        <th class="text-end">Energy & Labor (₹)</th>
                        <th class="text-end">Total Cost (₹)</th>
                        <th class="text-end">Cost per KG (₹)</th>
                        <th width="100" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costs as $idx => $c)
                    <tr>
                        <td>{{ $costs->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $c->batch->batch_no ?? '-' }}</td>
                        <td class="fw-semibold text-dark">{{ $c->batch->product->name ?? '-' }}</td>
                        <td class="text-end">₹{{ number_format($c->raw_material_cost, 2) }}</td>
                        <td class="text-end">₹{{ number_format($c->solvent_cost, 2) }}</td>
                        <td class="text-end">₹{{ number_format($c->utility_cost + $c->labor_cost, 2) }}</td>
                        <td class="text-end fw-bold text-dark">₹{{ number_format($c->total_cost, 2) }}</td>
                        <td class="text-end fw-bold text-success fs-6">₹{{ number_format($c->cost_per_unit, 2) }}</td>
                        <td class="text-end">
                            <a href="{{ route('costing.batch-cost.show', $c->batch) }}" class="btn btn-sm btn-icon btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No batch cost summaries calculated yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($costs->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $costs->firstItem() }} to {{ $costs->lastItem() }} of {{ $costs->total() }}</span>
            {{ $costs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection