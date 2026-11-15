@extends('layouts.app')
@section('title', 'Edit Parameter')
@section('content')
<div class="container-fluid px-0" style="max-width: 650px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Parameter</h2>
            <p class="page-subtitle">Update parameter limits and rules.</p>
        </div>
        <a href="{{ route('masters.parameters.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.parameters.update', $parameter) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label required">Parameter Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $parameter->name) }}" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Parameter Code</label>
                        <input type="text" class="form-control" value="{{ $parameter->code }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Parameter Type</label>
                        <select name="parameter_type" class="form-select" required>
                            <option value="Numeric" {{ $parameter->parameter_type === 'Numeric' ? 'selected' : '' }}>Numeric</option>
                            <option value="Text" {{ $parameter->parameter_type === 'Text' ? 'selected' : '' }}>Text</option>
                            <option value="Choice" {{ $parameter->parameter_type === 'Choice' ? 'selected' : '' }}>Choice</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Default UOM</label>
                        <input type="text" name="default_uom" class="form-control" value="{{ old('default_uom', $parameter->default_uom) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Min Limit</label>
                        <input type="number" step="0.01" name="min_value" class="form-control" value="{{ old('min_value', $parameter->min_value) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max Limit</label>
                        <input type="number" step="0.01" name="max_value" class="form-control" value="{{ old('max_value', $parameter->max_value) }}">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Target Value</label>
                        <input type="text" name="target_value" class="form-control" value="{{ old('target_value', $parameter->target_value) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $parameter->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $parameter->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="isMand" {{ $parameter->is_mandatory ? 'checked' : '' }}>
                    <label class="form-check-label" for="isMand">Mandatory Logging in Execution</label>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.parameters.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Parameter?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection