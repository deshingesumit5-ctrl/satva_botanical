@extends('layouts.app')
@section('title', 'Edit Role')
@section('content')
@php
    $isSuperAdminRole = in_array(strtolower((string) $role->role_code), ['super_admin', 'super admin'], true);
@endphp
<div class="container-fluid px-0">
    <!-- Page Header (Reference Layout) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-1 fw-bold text-uppercase mb-1" style="color: #059669; font-size: 12px; letter-spacing: 0.06em;">
                <i class="bi bi-shield-check"></i> ACCESS MANAGEMENT
            </div>
            <h2 class="page-title">Edit Role: {{ $role->role_name ?? $role->name }}</h2>
            <p class="page-subtitle">Update role details and assigned module access permissions.</p>
        </div>
        <div>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary rounded-3 px-3 py-2 fw-medium bg-white">
                <i class="bi bi-arrow-left me-1"></i> Back to Roles
            </a>
        </div>
    </div>

    <form action="{{ route('admin.roles.update', $role) }}" method="POST" id="editRoleForm">
        @csrf
        @method('PUT')

        <!-- Card 1: ROLE INFORMATION -->
        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-person-badge text-success fs-5"></i>
                    <span class="fw-bold text-uppercase" style="letter-spacing: 0.04em; font-size: 13px; color: #0f172a;">ROLE INFORMATION</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label required">Role Name</label>
                        <input type="text" name="role_name" value="{{ old('role_name', $role->role_name ?? $role->name) }}"
                            class="form-control @error('role_name') is-invalid @enderror"
                            placeholder="e.g. Manager, Sales Staff" required>
                        @error('role_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Description</label>
                        <input type="text" name="description" value="{{ old('description', $role->description) }}"
                            class="form-control @error('description') is-invalid @enderror"
                            placeholder="Brief description of this role">
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role Code</label>
                        <input type="text" class="form-control font-monospace" value="{{ $role->role_code ?? $role->code }}" readonly>
                    </div>
                </div>

                <input type="hidden" name="status" value="Inactive">
                <div class="form-check d-flex align-items-center gap-2 mt-2">
                    <input class="form-check-input mt-0" type="checkbox" name="status" value="Active" id="roleActiveCheck"
                        {{ old('status', $role->status ?? 'Active') === 'Active' ? 'checked' : '' }}
                        {{ $isSuperAdminRole ? 'onclick="return false;"' : '' }}>
                    <label class="form-check-label fw-semibold text-dark" for="roleActiveCheck" style="text-transform: none !important; font-size: 14px !important;">
                        Active <span class="text-muted fw-normal small ms-1">(Users with this role can login and access assigned modules)</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Card 2: MODULE PERMISSIONS -->
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-lock text-success fs-5"></i>
                    <span class="fw-bold text-uppercase" style="letter-spacing: 0.04em; font-size: 13px; color: #0f172a;">MODULE PERMISSIONS</span>
                    <span class="text-muted small">— Assign view/add/edit/delete access per module</span>
                </div>
                @if(!$isSuperAdminRole)
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCheckboxes(true)">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCheckboxes(false)">Deselect All</button>
                    </div>
                @else
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Super Admin has full access to all modules</span>
                @endif
            </div>

            @include('admin.permissions._matrix_table', [
                'sections' => $sections,
                'actions' => $actions,
                'assignedPermissionCodes' => $assignedPermissionCodes,
                'isReadonly' => $isSuperAdminRole,
            ])
        </div>

        <!-- Footer Actions -->
        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary-sattva">Save Role</button>
        </div>
    </form>
</div>
@endsection
