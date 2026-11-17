@extends('layouts.app')
@section('title', 'Permission Matrix')
@section('content')
@php
    $isSuperAdminRole = $selectedRole && in_array(strtolower((string) $selectedRole->role_code), ['super_admin', 'super admin'], true);
    $canEditMatrix = auth()->user() && (auth()->user()->hasPermission('admin.permissions.edit') || auth()->user()->hasPermission('admin.permissions.add'));
@endphp
<div class="container-fluid px-0">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-1 fw-bold text-uppercase mb-1" style="color: #059669; font-size: 12px; letter-spacing: 0.06em;">
                <i class="bi bi-shield-check"></i> ACCESS MANAGEMENT
            </div>
            <h2 class="page-title">Role Permission Matrix</h2>
            <p class="page-subtitle">Granular access control across modules (View, Add, Edit, Delete, Approve, Export, and operational actions).</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.permissions.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <label class="text-nowrap fw-semibold small mb-0">Select Role:</label>
                <select name="role_id" class="form-select" style="min-width: 220px;" onchange="this.form.submit()">
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" {{ $selectedRole && $selectedRole->id == $r->id ? 'selected' : '' }}>
                            {{ $r->role_name ?? $r->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($selectedRole)
        <form action="{{ route('admin.permissions.save') }}" method="POST" id="permissionMatrixForm">
            @csrf
            <input type="hidden" name="role_id" value="{{ $selectedRole->id }}">

            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                    <div>
                        <span class="fw-bold">Permissions for Role: <span class="text-success">{{ $selectedRole->role_name ?? $selectedRole->name }}</span></span>
                        @if($selectedRole->description)
                            <span class="text-muted small ms-2">— {{ $selectedRole->description }}</span>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if(!$isSuperAdminRole && $canEditMatrix)
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCheckboxes(true)">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleAllCheckboxes(false)">Deselect All</button>
                            <button type="submit" class="btn btn-sm btn-primary-sattva">
                                <i class="bi bi-check-lg me-1"></i> Save Matrix
                            </button>
                        @elseif($isSuperAdminRole)
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Super Admin inherently possesses all permissions</span>
                        @endif
                    </div>
                </div>

                @include('admin.permissions._matrix_table', [
                    'sections' => $sections,
                    'actions' => $actions,
                    'assignedPermissionCodes' => $assignedPermissionCodes,
                    'isReadonly' => $isSuperAdminRole || !$canEditMatrix,
                ])

                @if(!$isSuperAdminRole && $canEditMatrix)
                    <div class="card-footer bg-white text-end py-3 px-4">
                        <button type="submit" class="btn btn-primary-sattva px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Matrix
                        </button>
                    </div>
                @endif
            </div>
        </form>
    @else
        <div class="alert alert-info">Please select a role to configure permissions.</div>
    @endif
</div>
@endsection
