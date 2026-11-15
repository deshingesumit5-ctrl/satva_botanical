@extends('layouts.app')
@section('title', 'Add Material')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Add New Material</h2>
            <p class="page-subtitle">Register botanical raw materials, solvents or packaging.</p>
        </div>
        <a href="{{ route('masters.materials.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.materials.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Material Code</label>
                        <input type="text" name="material_code" class="form-control" required placeholder="e.g. RM-GARC-RIND">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Material Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Garcinia Cambogia Raw Rind (Dry)">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Material Type</label>
                        <select name="material_type" class="form-select" required>
                            <option value="Raw Material">Raw Material (Botanical)</option>
                            <option value="Solvent">Solvent (Liquid Extractant)</option>
                            <option value="Packaging">Packaging Material</option>
                            <option value="Chemical">Chemical / Reagent</option>
                            <option value="Consumable">Consumable</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Base Stock UOM</label>
                        <select name="base_uom_id" class="form-select" required>
                            <option value="">Select UOM</option>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control" placeholder="e.g. 121190">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Minimum Stock</label>
                        <input type="number" step="0.01" name="min_stock" class="form-control" placeholder="e.g. 500">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" step="0.01" name="reorder_level" class="form-control" placeholder="e.g. 1000">
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
                    <label class="form-label">Storage Conditions</label>
                    <textarea name="storage_conditions" class="form-control" rows="2" placeholder="Store in ventilated dry warehouse away from moisture..."></textarea>
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_hazardous" value="1" id="isHaz">
                    <label class="form-check-label" for="isHaz">Hazardous / Flammable Material (e.g. Methanol, Acetone, Acids)</label>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.materials.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Material</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection