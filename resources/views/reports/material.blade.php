@extends('layouts.app')
@section('title', 'Material Stock & Aging Report')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Material Stock & Aging Report</h2>
            <p class="page-subtitle">Trace raw herb inventories, solvent storage levels, lot aging and shelf life.</p>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('reports.material') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <select name="material_id" class="form-select">
                        <option value="">All Materials</option>
                        @foreach($materials as $m)
                            <option value="{{ $m->id }}" {{ request('material_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <a href="{{ route('reports.material') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Material</th>
                        <th>Warehouse</th>
                        <th>Received Qty</th>
                        <th>Current Available</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lots as $idx => $lot)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $lot->lot_number }}</td>
                        <td class="fw-semibold text-dark">{{ $lot->material->name ?? '-' }}</td>
                        <td>{{ $lot->warehouse->name ?? '-' }}</td>
                        <td>{{ $lot->initial_quantity }} {{ $lot->uom->symbol ?? '' }}</td>
                        <td class="fw-bold">{{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }}</td>
                        <td>{{ $lot->expiry_date ? \Carbon\Carbon::parse($lot->expiry_date)->format('d-m-Y') : '-' }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($lot->status) }}">{{ $lot->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No material stock lots found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection