@extends('layouts.app')
@section('title', 'Edit Plant')
@section('content')
<div class="container-fluid px-0" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Plant</h2>
            <p class="page-subtitle">Update plant parameters.</p>
        </div>
        <a href="{{ route('masters.plants.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.plants.update', $plant) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label required">Plant Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $plant->name) }}" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Plant Code</label>
                        <input type="text" class="form-control" value="{{ $plant->code }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="gstin" class="form-control" value="{{ old('gstin', $plant->gstin) }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $plant->address) }}</textarea>
                </div>
                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active" {{ $plant->status === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ $plant->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.plants.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Plant?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection