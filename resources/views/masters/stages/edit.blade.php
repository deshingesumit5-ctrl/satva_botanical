@extends('layouts.app')
@section('title', 'Edit Stage')
@section('content')
<div class="container-fluid px-0" style="max-width: 650px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Stage</h2>
            <p class="page-subtitle">Update stage parameters and controls.</p>
        </div>
        <a href="{{ route('masters.stages.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.stages.update', $stage) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label required">Stage Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $stage->name) }}" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Stage Code</label>
                        <input type="text" class="form-control" value="{{ $stage->code }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Default Sequence</label>
                        <input type="number" name="default_sequence" class="form-control" value="{{ old('default_sequence', $stage->default_sequence) }}" min="1">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Standard Duration (Hrs)</label>
                        <input type="number" step="0.1" name="standard_duration_hours" class="form-control" value="{{ old('standard_duration_hours', $stage->standard_duration_hours) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $stage->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $stage->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="recovery_applicable" value="1" id="recApp" {{ $stage->recovery_applicable ? 'checked' : '' }}>
                    <label class="form-check-label" for="recApp">Solvent Recovery Applicable</label>
                </div>
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="qc_mandatory_on_exit" value="1" id="qcMand" {{ $stage->qc_mandatory_on_exit ? 'checked' : '' }}>
                    <label class="form-check-label" for="qcMand">QC Inspection Mandatory On Stage Exit</label>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.stages.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Stage?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection