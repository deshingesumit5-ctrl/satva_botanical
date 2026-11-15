@extends('layouts.app')
@section('title', 'Edit Activity')
@section('content')
<div class="container-fluid px-0" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Activity</h2>
            <p class="page-subtitle">Update activity details and instructions.</p>
        </div>
        <a href="{{ route('masters.activities.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.activities.update', $activity) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label required">Activity Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $activity->name) }}" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Activity Code</label>
                        <input type="text" class="form-control" value="{{ $activity->code }}" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $activity->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $activity->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $activity->description) }}</textarea>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.activities.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Activity?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection