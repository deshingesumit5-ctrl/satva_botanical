@extends('layouts.app')
@section('title', 'Edit Equipment')
@section('content')
    <div class="container-fluid px-0" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Edit Equipment</h2>
                <p class="page-subtitle">Update equipment and calibration details.</p>
            </div>
            <a href="{{ route('masters.equipments.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('masters.equipments.update', $equipment) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label required">Equipment Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $equipment->name) }}"
                            required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Equipment Code</label>
                            <input type="text" class="form-control" value="{{ $equipment->code }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Equipment Type / Category</label>
                            <input type="text" name="category" class="form-control"
                                value="{{ old('category', $equipment->category ?? $equipment->equipment_type) }}" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Plant Facility</label>
                            <select name="plant_id" class="form-select">
                                <option value="">Select Plant</option>
                                @foreach(\App\Models\Plant::where('status', 'Active')->get() as $p)
                                    <option value="{{ $p->id }}" {{ $equipment->plant_id == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Equipment Status</label>
                            <select name="equipment_status" class="form-select" required>
                                <option value="Available" {{ $equipment->equipment_status === 'Available' ? 'selected' : '' }}>Available</option>
                                <option value="Under Maintenance" {{ ($equipment->equipment_status === 'Under Maintenance' || $equipment->status === 'Maintenance') ? 'selected' : '' }}>Under Maintenance</option>
                                <option value="Calibrating" {{ $equipment->equipment_status === 'Calibrating' ? 'selected' : '' }}>Calibrating</option>
                                <option value="In Use" {{ $equipment->equipment_status === 'In Use' ? 'selected' : '' }}>In
                                    Use</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" {{ $equipment->status === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $equipment->status === 'Inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Capacity</label>
                            <input type="number" step="0.01" name="capacity" class="form-control"
                                value="{{ old('capacity', $equipment->capacity) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Capacity UOM</label>
                            <input type="text" name="capacity_uom" class="form-control"
                                value="{{ old('capacity_uom', $equipment->capacity_uom) }}">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Next Calibration Due Date</label>
                        <input type="date" name="calibration_due_date" class="form-control"
                            value="{{ old('calibration_due_date', $equipment->calibration_due_date ? \Carbon\Carbon::parse($equipment->calibration_due_date)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('masters.equipments.index') }}" class="btn btn-light">Cancel</a>
                        <button type="button" class="btn btn-primary-sattva"
                            onclick="confirmEdit('Do you want to update this Equipment?', this.closest('form'))">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection