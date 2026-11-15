@extends('layouts.app')
@section('title', 'Permission Matrix')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Role Permission Matrix</h2>
            <p class="page-subtitle">Granular access control across modules (View, Create, Edit, Delete, Approve, Export).</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <form action="{{ route('admin.permissions.index') }}" method="GET" class="d-flex align-items-center gap-2">
                <label class="text-nowrap fw-semibold small mb-0">Select Role:</label>
                <select name="role_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" {{ $selectedRole && $selectedRole->id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    @if($selectedRole)
    <form action="{{ route('admin.permissions.update', ['role_id' => $selectedRole->id]) }}" method="POST">
        @csrf
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold">Permissions for Role: <span class="text-success">{{ $selectedRole->name }}</span></span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="toggleAllCheckboxes(true)">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="toggleAllCheckboxes(false)">Deselect All</button>
                    <button type="submit" class="btn btn-sm btn-primary-sattva"><i class="bi bi-check-lg me-1"></i> Save Matrix</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="220">Module</th>
                            <th width="300">Feature / Screen</th>
                            <th class="text-center">View</th>
                            <th class="text-center">Create</th>
                            <th class="text-center">Edit</th>
                            <th class="text-center">Delete</th>
                            <th class="text-center">Approve</th>
                            <th class="text-center">Export</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($matrix as $module => $features)
                            @foreach($features as $featureKey => $perms)
                            <tr>
                                @if($loop->first)
                                    <td rowspan="{{ count($features) }}" class="fw-bold align-middle bg-light text-uppercase small">{{ $module }}</td>
                                @endif
                                <td class="fw-semibold text-dark">{{ $featureKey }}</td>
                                @foreach(['view', 'create', 'edit', 'delete', 'approve', 'export'] as $action)
                                    <td class="text-center">
                                        @if(isset($perms[$action]))
                                            <input class="form-check-input perm-cb" type="checkbox" name="permissions[]" value="{{ $perms[$action]['code'] }}" {{ in_array($perms[$action]['code'], $assignedPermissionCodes) ? 'checked' : '' }}>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-end py-3">
                <button type="submit" class="btn btn-primary-sattva px-4"><i class="bi bi-save me-1"></i> Save Changes</button>
            </div>
        </div>
    </form>
    @else
        <div class="alert alert-info">Please select a role to configure permissions.</div>
    @endif
</div>

<script>
function toggleAllCheckboxes(checked) {
    document.querySelectorAll('.perm-cb').forEach(cb => cb.checked = checked);
}
</script>
@endsection