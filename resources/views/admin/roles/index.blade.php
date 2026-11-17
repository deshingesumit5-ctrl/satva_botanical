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
                @if(auth()->user() && auth()->user()->hasPermission('admin.permissions.view'))
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-grid-3x3-gap me-1"></i> Permission Matrix
                    </a>
                @endif
                @if(auth()->user() && auth()->user()->hasPermission('admin.roles.add'))
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary-sattva">
                        <i class="bi bi-plus-lg me-1"></i> Add Role
                    </a>
                @endif
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
                            <th>Permissions</th>
                            <th>Assigned Users</th>
                            <th>Status</th>
                            <th width="140" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $idx => $r)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="fw-semibold text-dark">{{ $r->role_name ?? $r->name }}</td>
                                <td><code>{{ $r->role_code ?? $r->code }}</code></td>
                                <td class="text-muted small">{{ $r->description ?? '-' }}</td>
                                <td>
                                    @if(in_array(strtolower((string) ($r->role_code ?? $r->code)), ['super_admin', 'super admin'], true))
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">All Permissions</span>
                                    @else
                                        <span class="badge bg-light text-dark border">{{ $r->permissions_count ?? $r->permissions()->count() }} Assigned</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $r->users_count ?? $r->users()->count() }} Users</span>
                                </td>
                                <td>
                                    <span class="badge-status badge-status-{{ strtolower($r->status) }}">{{ $r->status }}</span>
                                </td>
                                <td class="text-end">
                                    @if(auth()->user() && auth()->user()->hasPermission('admin.permissions.view'))
                                        <a href="{{ route('admin.permissions.index', ['role_id' => $r->id]) }}"
                                            class="btn btn-sm btn-icon btn-outline-info me-1" title="Configure Matrix">
                                            <i class="bi bi-shield-lock"></i>
                                        </a>
                                    @endif
                                    @if(auth()->user() && auth()->user()->hasPermission('admin.roles.edit'))
                                        <a href="{{ route('admin.roles.edit', $r) }}"
                                            class="btn btn-sm btn-icon btn-outline-primary me-1" title="Edit Role">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    @if(auth()->user() && auth()->user()->hasPermission('admin.roles.delete') && !in_array($r->role_code ?? $r->code, ['super_admin', 'super-admin', 'admin']))
                                        <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                            data-url="{{ route('admin.roles.destroy', $r) }}"
                                            data-name="{{ $r->role_name ?? $r->name }}" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
