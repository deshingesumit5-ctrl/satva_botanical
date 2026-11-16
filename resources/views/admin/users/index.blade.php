@extends('layouts.app')
@section('title', 'User Management')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">User Master</h2>
                <p class="page-subtitle">Manage system users, login credentials, assigned roles and plant access.</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-person-plus me-1"></i> Add New User
            </a>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('admin.users.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search name, username, email, emp id..."></div>
                    <div class="col-md-3">
                        <select name="role_id" class="form-select">
                            <option value="">All Roles</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-sattva">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>User Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Plant</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th width="120" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $idx => $u)
                            <tr>
                                <td>{{ $users->firstItem() + $idx }}</td>
                                <td class="fw-semibold text-dark">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar-circle-sm">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                        <div>
                                            <div>{{ $u->name }}</div>
                                            <small class="text-muted">{{ $u->employee_code ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><code>{{ $u->username }}</code></td>
                                <td>{{ $u->email }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $u->role->name ?? '-' }}</span></td>
                                <td>{{ $u->plant->name ?? 'All Plants' }}</td>
                                <td>{{ $u->department->name ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('admin.users.toggle-status', $u) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            onclick="confirmAction('Toggle active status for {{ $u->name }}?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($u->status) }}">{{ $u->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $u) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    @if($u->id !== auth()->id())
                                        <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                            data-url="{{ route('admin.users.destroy', $u) }}" data-name="{{ $u->name }}"
                                            title="Delete"><i class="bi bi-trash"></i></button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($users->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of
                        {{ $users->total() }}</span>
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection