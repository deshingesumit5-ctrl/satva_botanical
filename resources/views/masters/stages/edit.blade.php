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
                    <div class="mb-3">
                        <label class="form-label">Default Equipment</label>
                        <select name="default_equipment_id" class="form-select">
                            <option value="">None / Select Equipment</option>
                            @foreach($equipments as $eq)
                                <option value="{{ $eq->id }}" {{ old('default_equipment_id', $stage->default_equipment_id) == $eq->id ? 'selected' : '' }}>
                                    {{ $eq->name }} ({{ $eq->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                            placeholder="Optional process stage notes...">{{ old('description', $stage->description) }}</textarea>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <input type="hidden" name="activity_required" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="activity_required" value="1" id="actReq"
                                    {{ old('activity_required', $stage->activity_required) ? 'checked' : '' }}>
                                <label class="form-check-label" for="actReq">Activity Logging Required</label>
                            </div>
                            <input type="hidden" name="material_input_required" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="material_input_required" value="1" id="matInReq"
                                    {{ old('material_input_required', $stage->material_input_required) ? 'checked' : '' }}>
                                <label class="form-check-label" for="matInReq">Material Input Required</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <input type="hidden" name="material_output_required" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="material_output_required" value="1" id="matOutReq"
                                    {{ old('material_output_required', $stage->material_output_required) ? 'checked' : '' }}>
                                <label class="form-check-label" for="matOutReq">Material Output Required</label>
                            </div>
                            <input type="hidden" name="qc_available" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="qc_available" value="1" id="qcAvail"
                                    {{ old('qc_available', $stage->qc_available) ? 'checked' : '' }}>
                                <label class="form-check-label" for="qcAvail">QC Inspection Available</label>
                            </div>
                        </div>
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