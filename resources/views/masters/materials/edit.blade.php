@extends('layouts.app')
@section('title', 'Edit Material')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Material: {{ $material->name }}</h2>
            <p class="page-subtitle">Update material properties and stock reorder parameters.</p>
        </div>
        <a href="{{ route('masters.materials.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.materials.update', $material) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Material Code</label>
                        <input type="text" class="form-control" value="{{ $material->material_code }}" disabled>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Material Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $material->name) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Material Type</label>
                        <select name="material_type" class="form-select" required>
                            <option value="Raw Material" {{ $material->material_type === 'Raw Material' ? 'selected' : '' }}>Raw Material (Botanical)</option>
                            <option value="Solvent" {{ $material->material_type === 'Solvent' ? 'selected' : '' }}>Solvent (Liquid Extractant)</option>
                            <option value="Packaging" {{ $material->material_type === 'Packaging' ? 'selected' : '' }}>Packaging Material</option>
                            <option value="Chemical" {{ $material->material_type === 'Chemical' ? 'selected' : '' }}>Chemical / Reagent</option>
                            <option value="Consumable" {{ $material->material_type === 'Consumable' ? 'selected' : '' }}>Consumable</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Base Stock UOM</label>
                        <select name="base_uom_id" class="form-select" required>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}" {{ $material->base_uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control" value="{{ old('hsn_code', $material->hsn_code) }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Minimum Stock</label>
                        <input type="number" step="0.01" name="min_stock" class="form-control" value="{{ old('min_stock', $material->min_stock) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" step="0.01" name="reorder_level" class="form-control" value="{{ old('reorder_level', $material->reorder_level) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $material->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $material->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Storage Conditions</label>
                    <textarea name="storage_conditions" class="form-control" rows="2">{{ old('storage_conditions', $material->storage_conditions) }}</textarea>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_hazardous" value="1" id="isHaz" {{ $material->is_hazardous ? 'checked' : '' }}>
                    <label class="form-check-label" for="isHaz">Hazardous / Flammable Material</label>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.materials.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Material?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection