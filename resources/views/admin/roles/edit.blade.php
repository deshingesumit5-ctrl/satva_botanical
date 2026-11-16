@extends('layouts.app')
@section('title', 'Edit Role')
@section('content')
    <div class="container-fluid px-0" style="max-width: 600px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Edit Role: {{ $role->role_name ?? $role->name }}</h2>
                <p class="page-subtitle">Update role metadata.</p>
            </div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label required">Role Name</label>
                        <input type="text" name="role_name" class="form-control"
                            value="{{ old('role_name', $role->role_name ?? $role->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role Code</label>
                        <input type="text" class="form-control" value="{{ $role->role_code ?? $role->code }}" readonly
                            disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"
                            rows="2">{{ old('description', $role->description) }}</textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $role->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $role->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
                        <button type="button" class="btn btn-primary-sattva"
                            onclick="confirmEdit('Do you want to update this Role?', this.closest('form'))">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection