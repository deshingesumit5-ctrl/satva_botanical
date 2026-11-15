@extends('layouts.app')
@section('title', 'Stock Ledger')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Stock Ledger & Movement Audit</h2>
            <p class="page-subtitle">Complete chronological inward/outward material transaction register.</p>
        </div>
        <a href="{{ route('inventory.stock.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Stock</a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('inventory.stock.ledger') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search reference number, lot..."></div>
                <div class="col-md-3">
                    <select name="material_id" class="form-select">
                        <option value="">All Materials</option>
                        @foreach($materials as $m)
                            <option value="{{ $m->id }}" {{ request('material_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('inventory.stock.ledger') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Item</th>
                        <th>Warehouse</th>
                        <th>Ref Number</th>
                        <th class="text-end">In Qty</th>
                        <th class="text-end">Out Qty</th>
                        <th class="text-end">Balance Qty</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerEntries as $entry)
                    <tr>
                        <td>{{ $entry->created_at->format('d-m-Y H:i') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ strtoupper($entry->transaction_type) }}</span></td>
                        <td>{{ $entry->material->name ?? $entry->product->name ?? '-' }}</td>
                        <td>{{ $entry->warehouse->name ?? '-' }}</td>
                        <td><code>{{ $entry->reference_number ?? '-' }}</code></td>
                        <td class="text-end text-success fw-bold">{{ $entry->quantity > 0 ? '+'.$entry->quantity : '-' }}</td>
                        <td class="text-end text-danger fw-bold">{{ $entry->quantity < 0 ? $entry->quantity : '-' }}</td>
                        <td class="text-end fw-bold">{{ $entry->balance_after }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No stock ledger transactions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($ledgerEntries->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $ledgerEntries->firstItem() }} to {{ $ledgerEntries->lastItem() }} of {{ $ledgerEntries->total() }}</span>
            {{ $ledgerEntries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection