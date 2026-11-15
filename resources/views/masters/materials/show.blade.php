@extends('layouts.app')
@section('title', 'Material: ' . $material->name)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $material->name }}</h2>
                <span class="badge-status badge-status-{{ strtolower($material->status) }}">{{ $material->status }}</span>
            </div>
            <p class="page-subtitle">Material Code: {{ $material->material_code }} | Type: {{ $material->material_type }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('masters.materials.edit', $material) }}" class="btn btn-primary-sattva"><i class="bi bi-pencil me-1"></i> Edit</a>
            <a href="{{ route('masters.materials.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Material Properties</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="text-muted small">Type</label><div class="fw-semibold">{{ $material->material_type }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Stock UOM</label><div class="fw-semibold">{{ $material->baseUom->name ?? '-' }} ({{ $material->baseUom->symbol ?? '' }})</div></div>
                        <div class="col-md-4"><label class="text-muted small">HSN Code</label><div class="fw-semibold">{{ $material->hsn_code ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Min Stock</label><div class="fw-semibold">{{ $material->min_stock ?? 0 }} {{ $material->baseUom->symbol ?? '' }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Reorder Level</label><div class="fw-semibold">{{ $material->reorder_level ?? 0 }} {{ $material->baseUom->symbol ?? '' }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Hazardous</label><div><span class="badge {{ $material->is_hazardous ? 'bg-danger' : 'bg-light text-dark' }}">{{ $material->is_hazardous ? 'Yes' : 'No' }}</span></div></div>
                    </div>
                    <hr class="my-3">
                    <div>
                        <label class="text-muted small">Storage Conditions</label>
                        <p class="mb-0">{{ $material->storage_conditions ?? 'Standard dry storage conditions.' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Active Stock Lots</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="text-center py-3">
                        <div class="h3 fw-bold text-success mb-0">{{ $material->stockLots->sum('current_quantity') }}</div>
                        <div class="text-muted small">Current Total Available ({{ $material->baseUom->symbol ?? '' }})</div>
                    </div>
                    <a href="{{ route('inventory.stock.lots', ['material_id' => $material->id]) }}" class="btn btn-outline-secondary btn-sm w-100">View Lot Inventory</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection