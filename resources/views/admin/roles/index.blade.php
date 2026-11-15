@extends('layouts.app')
@section('title', 'Role Master')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Role Master</h2>
            <p class="page-subtitle">Configure system roles (Super Admin, Production Manager, QC Chemist, Store Officer, etc.).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-grid-3x3-gap me-1"></i> Permission Matrix</a>
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                <i class="bi bi-plus-lg me-1"></i> Add Role
            </button>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Role Name</th>
                        <th>Slug / Code</th>
                        <th>Description</th>
                        <th>Assigned Users</th>
                        <th>Status</th>
                        <th width="140" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $idx => $r)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-semibold text-dark">{{ $r->name }}</td>
                        <td><code>{{ $r->code }}</code></td>
                        <td class="text-muted small">{{ $r->description ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $r->users_count ?? $r->users()->count() }} Users</span></td>
                        <td><span class="badge-status badge-status-{{ strtolower($r->status) }}">{{ $r->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.permissions.index', ['role_id' => $r->id]) }}" class="btn btn-sm btn-icon btn-outline-info me-1" title="Configure Matrix"><i class="bi bi-shield-lock"></i></a>
                            <a href="{{ route('admin.roles.edit', $r) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            @if(!in_array($r->code, ['super-admin', 'admin']))
                            <form action="{{ route('admin.roles.destroy', $r) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete('Do you want to delete this Role?', '{{ $r->name }}', this.closest('form'))"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No roles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('admin.roles.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Role Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Production Supervisor">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role Code / Slug</label>
                    <input type="text" name="code" class="form-control" placeholder="Auto-generated if empty">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Responsibilities of this role..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-sattva">Save Role</button>
            </div>
        </form>
    </div>
</div>
@endsection