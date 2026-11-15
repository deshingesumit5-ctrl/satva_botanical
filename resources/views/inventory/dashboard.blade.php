@extends('layouts.app')
@section('title', 'Inventory & Warehouse Dashboard')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Warehouse & Inventory Dashboard</h2>
            <p class="page-subtitle">Real-time stock valuation, raw material inventory, solvent tank occupancy, and batch lot tracking.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.stock.opening-entry') }}" class="btn btn-outline-secondary"><i class="bi bi-box-seam me-1"></i> Opening Stock Entry</a>
            <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary-sattva"><i class="bi bi-arrow-left-right me-1"></i> Stock Transfer</a>
        </div>
    </div>

    <!-- Stat KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-primary">
                <span class="text-muted small">Total Materials (Raw/Solvents)</span>
                <div class="h3 fw-bold text-dark mb-0 mt-1">{{ $totalMaterials }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-success">
                <span class="text-muted small">Finished Good Products</span>
                <div class="h3 fw-bold text-dark mb-0 mt-1">{{ $totalProducts }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-warning">
                <span class="text-muted small">Low Stock Alerts</span>
                <div class="h3 fw-bold text-warning mb-0 mt-1">{{ $lowStockCount }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-sattva p-3 border-start border-4 border-danger">
                <span class="text-muted small">Expired / Near Expiry Lots</span>
                <div class="h3 fw-bold text-danger mb-0 mt-1">{{ $expiredCount + $nearExpiryCount }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Warehouse Occupancy -->
        <div class="col-md-6">
            <div class="card card-sattva h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Warehouses & Storage Facilities</h6></div>
                <div class="card-sattva-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Warehouse</th>
                                    <th>Type</th>
                                    <th>Plant</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($warehouses as $wh)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $wh->name }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $wh->warehouse_type ?? $wh->type }}</span></td>
                                    <td>{{ $wh->plant->name ?? '-' }}</td>
                                    <td><span class="badge-status badge-status-{{ strtolower($wh->status) }}">{{ $wh->status }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiring Lots Alert -->
        <div class="col-md-6">
            <div class="card card-sattva h-100">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold text-danger"><i class="bi bi-clock-history me-1"></i> Near Expiry Lots (Next 60 Days)</h6></div>
                <div class="card-sattva-body p-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Lot Number</th>
                                    <th>Item</th>
                                    <th>Available Qty</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expiringLots as $lot)
                                <tr>
                                    <td class="fw-bold font-monospace">{{ $lot->lot_number }}</td>
                                    <td>{{ $lot->material->name ?? $lot->product->name ?? '-' }}</td>
                                    <td class="fw-semibold">{{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }}</td>
                                    <td class="text-danger fw-bold">{{ $lot->expiry_date ? \Carbon\Carbon::parse($lot->expiry_date)->format('d-m-Y') : '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-4 text-muted">No lots expiring soon.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection