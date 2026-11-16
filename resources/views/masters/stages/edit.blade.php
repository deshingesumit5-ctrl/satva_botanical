@extends('layouts.app')
@section('title', 'Edit Stage')
@section('content')
    <div class="container-fluid px-0" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Edit Stage: {{ $stage->stage_name ?? $stage->name }}</h2>
                <p class="page-subtitle">Update stage parameters and controls.</p>
            </div>
            <a href="{{ route('masters.stages.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('masters.stages.update', $stage) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Stage Code</label>
                            <input type="text" class="form-control" value="{{ $stage->stage_code ?? $stage->code }}"
                                disabled>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Stage Name</label>
                            <input type="text" name="stage_name" class="form-control"
                                value="{{ old('stage_name', $stage->stage_name ?? $stage->name) }}" required>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="Processing" {{ ($stage->category === 'Processing') ? 'selected' : '' }}>
                                    Processing</option>
                                <option value="Treatment" {{ ($stage->category === 'Treatment') ? 'selected' : '' }}>Treatment
                                </option>
                                <option value="Separation" {{ ($stage->category === 'Separation') ? 'selected' : '' }}>
                                    Separation</option>
                                <option value="Drying" {{ ($stage->category === 'Drying') ? 'selected' : '' }}>Drying</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" {{ $stage->status === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $stage->status === 'Inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Default Duration</label>
                            <input type="number" name="default_duration" class="form-control"
                                value="{{ old('default_duration', $stage->default_duration ?? $stage->standard_duration_hours) }}"
                                min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Duration UOM</label>
                            <select name="duration_uom" class="form-select" required>
                                <option value="Hours" {{ ($stage->duration_uom === 'Hours') ? 'selected' : '' }}>Hours
                                </option>
                                <option value="Minutes" {{ ($stage->duration_uom === 'Minutes') ? 'selected' : '' }}>Minutes
                                </option>
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
                        <button type="button" class="btn btn-primary-sattva"
                            onclick="confirmEdit('Do you want to update this Stage?', this.closest('form'))">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection