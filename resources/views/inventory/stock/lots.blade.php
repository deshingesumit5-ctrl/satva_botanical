@extends('layouts.app')
@section('title', 'Lot Inventory')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Traceable Lot Inventory</h2>
            <p class="page-subtitle">Batch and lot-wise physical stock tracking with quarantine and release controls.</p>
        </div>
        <a href="{{ route('inventory.stock.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Stock</a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('inventory.stock.lots') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search lot number..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Available" {{ request('status') === 'Available' ? 'selected' : '' }}>Available</option>
                        <option value="Quarantine" {{ request('status') === 'Quarantine' ? 'selected' : '' }}>Quarantine</option>
                        <option value="Reserved" {{ request('status') === 'Reserved' ? 'selected' : '' }}>Reserved</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('inventory.stock.lots') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Lot Number</th>
                        <th>Item</th>
                        <th>Warehouse</th>
                        <th>Original Qty</th>
                        <th>Current Available</th>
                        <th>Unit Rate</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lots as $idx => $lot)
                    <tr>
                        <td>{{ $lots->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $lot->lot_number }}</td>
                        <td class="fw-semibold text-dark">{{ $lot->material->name ?? $lot->product->name ?? '-' }}</td>
                        <td>{{ $lot->warehouse->name ?? '-' }}</td>
                        <td>{{ $lot->initial_quantity }} {{ $lot->uom->symbol ?? '' }}</td>
                        <td class="fw-bold">{{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }}</td>
                        <td>₹{{ number_format($lot->unit_cost ?? 0, 2) }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($lot->status) }}">{{ $lot->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No lots found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lots->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $lots->firstItem() }} to {{ $lots->lastItem() }} of {{ $lots->total() }}</span>
            {{ $lots->links() }}
        </div>
        @endif
    </div>
</div>
@endsection