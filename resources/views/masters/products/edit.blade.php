@extends('layouts.app')
@section('title', 'Edit Product')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Product: {{ $product->name }}</h2>
            <p class="page-subtitle">Update specifications, shelf life and configuration.</p>
        </div>
        <a href="{{ route('masters.products.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.products.update', $product) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Product Code</label>
                        <input type="text" class="form-control" value="{{ $product->product_code }}" disabled>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Product Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Product Category</label>
                        <select name="product_category_id" class="form-select" required>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $product->product_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Base UOM</label>
                        <select name="base_uom_id" class="form-select" required>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}" {{ $product->base_uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control" value="{{ old('hsn_code', $product->hsn_code) }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Shelf Life (Months)</label>
                        <input type="number" name="shelf_life" class="form-control" value="{{ old('shelf_life', $product->shelf_life) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Minimum Stock Level</label>
                        <input type="number" step="0.01" name="min_stock" class="form-control" value="{{ old('min_stock', $product->min_stock) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $product->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $product->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description & Physical Specifications</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Storage Instructions</label>
                    <textarea name="storage_instructions" class="form-control" rows="2">{{ old('storage_instructions', $product->storage_instructions) }}</textarea>
                </div>

                <div class="row g-3 mb-4 p-3 bg-light rounded">
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="batch_tracking" value="1" id="batchTrack" {{ $product->batch_tracking ? 'checked' : '' }}>
                            <label class="form-check-label" for="batchTrack">Batch Tracking Required</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="qc_required" value="1" id="qcReq" {{ $product->qc_required ? 'checked' : '' }}>
                            <label class="form-check-label" for="qcReq">Mandatory QC Testing</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="manufacturing_date_required" value="1" id="mfgReq" {{ $product->manufacturing_date_required ? 'checked' : '' }}>
                            <label class="form-check-label" for="mfgReq">Require Mfg Date</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="expiry_date_required" value="1" id="expReq" {{ $product->expiry_date_required ? 'checked' : '' }}>
                            <label class="form-check-label" for="expReq">Require Expiry Date</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.products.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Product?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection