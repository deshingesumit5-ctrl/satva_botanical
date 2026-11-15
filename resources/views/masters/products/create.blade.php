@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Add New Product</h2>
            <p class="page-subtitle">Register botanical finished extract or saleable product.</p>
        </div>
        <a href="{{ route('masters.products.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.products.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Product Code</label>
                        <input type="text" name="product_code" class="form-control" required placeholder="e.g. FG-GARC-60">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Product Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Garcinia Cambogia Extract 60%">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Product Category</label>
                        <select name="product_category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Base UOM</label>
                        <select name="base_uom_id" class="form-select" required>
                            <option value="">Select UOM</option>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control" placeholder="e.g. 13021919">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Shelf Life (Months)</label>
                        <input type="number" name="shelf_life" class="form-control" placeholder="e.g. 24">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Minimum Stock Level</label>
                        <input type="number" step="0.01" name="min_stock" class="form-control" placeholder="e.g. 100">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description & Physical Specifications</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Appearance, color, mesh size..."></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">Storage Instructions</label>
                    <textarea name="storage_instructions" class="form-control" rows="2" placeholder="Store in airtight containers below 25°C in dry place..."></textarea>
                </div>

                <div class="row g-3 mb-4 p-3 bg-light rounded">
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="batch_tracking" value="1" id="batchTrack" checked>
                            <label class="form-check-label" for="batchTrack">Batch Tracking Required</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="qc_required" value="1" id="qcReq" checked>
                            <label class="form-check-label" for="qcReq">Mandatory QC Testing</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="manufacturing_date_required" value="1" id="mfgReq" checked>
                            <label class="form-check-label" for="mfgReq">Require Mfg Date</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="expiry_date_required" value="1" id="expReq" checked>
                            <label class="form-check-label" for="expReq">Require Expiry Date</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.products.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection