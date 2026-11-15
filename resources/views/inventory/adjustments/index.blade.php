@extends('layouts.app')
@section('title', 'Stock Adjustments')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Stock Adjustments & Write-Offs</h2>
            <p class="page-subtitle">Physical stock audit reconciliations, moisture loss variances, and spill adjustments.</p>
        </div>
        <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> New Adjustment
        </a>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Date</th>
                        <th>Lot Number</th>
                        <th>Item</th>
                        <th>Warehouse</th>
                        <th>Type</th>
                        <th class="text-end">Adjusted Qty</th>
                        <th>Reason / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($adjustments as $idx => $adj)
                    <tr>
                        <td>{{ $adjustments->firstItem() + $idx }}</td>
                        <td>{{ $adj->created_at->format('d-m-Y H:i') }}</td>
                        <td class="fw-bold font-monospace">{{ $adj->stockLot->lot_number ?? '-' }}</td>
                        <td>{{ $adj->stockLot->material->name ?? $adj->stockLot->product->name ?? '-' }}</td>
                        <td>{{ $adj->stockLot->warehouse->name ?? '-' }}</td>
                        <td><span class="badge {{ $adj->adjustment_type === 'Addition' ? 'bg-success' : 'bg-danger' }}">{{ $adj->adjustment_type }}</span></td>
                        <td class="text-end fw-bold {{ $adj->adjustment_type === 'Addition' ? 'text-success' : 'text-danger' }}">
                            {{ $adj->adjustment_type === 'Addition' ? '+' : '-' }}{{ $adj->quantity }}
                        </td>
                        <td>{{ $adj->reason }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No stock adjustments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($adjustments->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $adjustments->firstItem() }} to {{ $adjustments->lastItem() }} of {{ $adjustments->total() }}</span>
            {{ $adjustments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection