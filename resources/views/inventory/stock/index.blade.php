@extends('layouts.app')
@section('title', 'Current Stock')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Current Inventory Balances</h2>
            <p class="page-subtitle">Real-time on-hand stock balances across all plant warehouses and cold storage.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.stock.lots') }}" class="btn btn-outline-secondary"><i class="bi bi-boxes me-1"></i> View Lot Inventory</a>
            <a href="{{ route('inventory.stock.ledger') }}" class="btn btn-outline-info"><i class="bi bi-journal-text me-1"></i> Stock Ledger</a>
            <a href="{{ route('inventory.stock.opening-entry') }}" class="btn btn-primary-sattva"><i class="bi bi-plus-lg me-1"></i> Opening Stock</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('inventory.stock.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search lot number, material, product..."></div>
                <div class="col-md-3">
                    <select name="warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('inventory.stock.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Item Description</th>
                        <th>Warehouse</th>
                        <th>Rack / Bin</th>
                        <th>Current Quantity</th>
                        <th>UOM</th>
                        <th>Expiry Date</th>
                        <th>QC Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lots as $idx => $lot)
                    <tr>
                        <td>{{ $lots->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $lot->lot_number }}</td>
                        <td class="fw-semibold text-dark">
                            {{ $lot->material->name ?? $lot->product->name ?? '-' }}
                            <small class="text-muted d-block">{{ $lot->material ? 'Raw Material' : 'Finished Good' }}</small>
                        </td>
                        <td>{{ $lot->warehouse->name ?? '-' }}</td>
                        <td>{{ $lot->storageLocation->name ?? '-' }}</td>
                        <td class="fw-bold">{{ $lot->current_quantity }}</td>
                        <td>{{ $lot->uom->symbol ?? '' }}</td>
                        <td>{{ $lot->expiry_date ? \Carbon\Carbon::parse($lot->expiry_date)->format('d-m-Y') : '-' }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($lot->status) }}">{{ $lot->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No stock lots found.</td></tr>
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