@extends('layouts.app')
@section('title', 'Product: ' . $product->name)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $product->name }}</h2>
                <span class="badge-status badge-status-{{ strtolower($product->status) }}">{{ $product->status }}</span>
            </div>
            <p class="page-subtitle">Product Code: {{ $product->product_code }} | Category: {{ $product->category->name ?? 'None' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('masters.products.edit', $product) }}" class="btn btn-primary-sattva"><i class="bi bi-pencil me-1"></i> Edit</a>
            <a href="{{ route('masters.products.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">General Information</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="text-muted small">Category</label><div class="fw-semibold">{{ $product->category->name ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Base UOM</label><div class="fw-semibold">{{ $product->baseUom->name ?? '-' }} ({{ $product->baseUom->symbol ?? '' }})</div></div>
                        <div class="col-md-4"><label class="text-muted small">HSN Code</label><div class="fw-semibold">{{ $product->hsn_code ?? '-' }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Shelf Life</label><div class="fw-semibold">{{ $product->shelf_life ? $product->shelf_life.' Months' : '-' }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Min Stock Level</label><div class="fw-semibold">{{ $product->min_stock ?? 0 }} {{ $product->baseUom->symbol ?? '' }}</div></div>
                        <div class="col-md-4"><label class="text-muted small">Batch Tracking</label><div class="fw-semibold">{{ $product->batch_tracking ? 'Yes' : 'No' }}</div></div>
                    </div>
                    <hr class="my-3">
                    <div class="mb-3">
                        <label class="text-muted small">Description</label>
                        <p class="mb-0">{{ $product->description ?? 'No description provided.' }}</p>
                    </div>
                    <div>
                        <label class="text-muted small">Storage Instructions</label>
                        <p class="mb-0">{{ $product->storage_instructions ?? 'Standard room temperature storage.' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Quality & Compliance</h6></div>
                <div class="card-sattva-body p-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>QC Testing Required</span>
                            <span class="badge {{ $product->qc_required ? 'bg-success' : 'bg-secondary' }}">{{ $product->qc_required ? 'Yes' : 'No' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>Mfg Date Required</span>
                            <span class="badge {{ $product->manufacturing_date_required ? 'bg-success' : 'bg-secondary' }}">{{ $product->manufacturing_date_required ? 'Yes' : 'No' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>Expiry Date Required</span>
                            <span class="badge {{ $product->expiry_date_required ? 'bg-success' : 'bg-secondary' }}">{{ $product->expiry_date_required ? 'Yes' : 'No' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection