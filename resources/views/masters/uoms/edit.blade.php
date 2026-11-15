@extends('layouts.app')
@section('title', 'Edit UOM')
@section('content')
<div class="container-fluid px-0" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit UOM</h2>
            <p class="page-subtitle">Update unit of measurement properties.</p>
        </div>
        <a href="{{ route('masters.uoms.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.uoms.update', $uom) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label required">UOM Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $uom->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Symbol</label>
                    <input type="text" name="symbol" class="form-control" value="{{ old('symbol', $uom->symbol) }}" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Decimal Places</label>
                        <input type="number" name="decimal_places" class="form-control" value="{{ old('decimal_places', $uom->decimal_places) }}" min="0" max="6">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $uom->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $uom->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_stock_unit" value="1" id="isStockUnit" {{ $uom->is_stock_unit ? 'checked' : '' }}>
                    <label class="form-check-label" for="isStockUnit">Allow as Primary Stock Unit</label>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.uoms.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this UOM?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection