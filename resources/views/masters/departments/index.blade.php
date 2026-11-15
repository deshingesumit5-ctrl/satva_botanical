@extends('layouts.app')
@section('title', 'Departments')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Department Master</h2>
            <p class="page-subtitle">Organize organizational departments and department heads.</p>
        </div>
        <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="bi bi-plus-lg me-1"></i> Add Department
        </button>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('masters.departments.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search department name, code, head..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('masters.departments.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Code</th><th>Department Name</th><th>Plant</th><th>Department Head</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse($departments as $d)
                        <tr>
                            <td class="fw-bold">{{ $d->code }}</td>
                            <td>{{ $d->name }}</td>
                            <td>{{ $d->plant ? $d->plant->name : 'All Plants' }}</td>
                            <td>{{ $d->department_head ?? '-' }}</td>
                            <td><x-status-badge :status="$d->status" /></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary rounded-circle p-1 me-1" onclick="editDept({{ $d->id }})" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-outline-danger rounded-circle p-1" onclick="deleteDept({{ $d->id }}, '{{ addslashes($d->name) }}')" title="Delete"><i class="bi bi-trash"></i></button>
                                <form id="del-dept-{{ $d->id }}" action="{{ route('masters.departments.destroy', $d->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No departments configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())<div class="p-3 border-top">{{ $departments->links() }}</div>@endif
    </div>
</div>

<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Add Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="{{ route('masters.departments.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small fw-semibold">Code *</label><input type="text" name="code" class="form-control" placeholder="e.g. DEPT-QC" required></div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Department Name *</label><input type="text" name="name" class="form-control" placeholder="e.g. Quality Control" required></div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Plant</label>
                        <select name="plant_id" class="form-select">
                            <option value="">Select Plant</option>
                            @foreach($plants as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Department Head</label><input type="text" name="department_head" class="form-control" placeholder="Name of Manager / Lead"></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="Active" id="dActive" checked><label class="form-check-label fw-semibold" for="dActive">Active</label></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary-sattva">Save</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editDeptModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Update Department</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editDeptForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label small fw-semibold">Department Name *</label><input type="text" name="name" id="edName" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Plant</label>
                        <select name="plant_id" id="edPlantId" class="form-select">
                            <option value="">Select Plant</option>
                            @foreach($plants as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-semibold">Department Head</label><input type="text" name="department_head" id="edHead" class="form-control"></div>
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="Active" id="edStatus"><label class="form-check-label fw-semibold" for="edStatus">Active</label></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary-sattva" onclick="confirmUpdateDept()">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function editDept(id) {
        fetch(`/masters/departments/${id}/edit`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(res => res.json())
            .then(data => {
                document.getElementById('editDeptForm').action = `/masters/departments/${id}`;
                document.getElementById('edName').value = data.name;
                document.getElementById('edPlantId').value = data.plant_id || '';
                document.getElementById('edHead').value = data.department_head || '';
                document.getElementById('edStatus').checked = (data.status === 'Active');
                new bootstrap.Modal(document.getElementById('editDeptModal')).show();
            });
    }
    function confirmUpdateDept() {
        openConfirmModal({
            title: 'Do you want to update this?',
            message: 'Save changes to this department record.',
            icon: 'bi-pencil-square',
            iconColor: '#059669',
            confirmText: 'Yes, Update',
            confirmClass: 'btn-success',
            onConfirm: () => document.getElementById('editDeptForm').submit()
        });
    }
    function deleteDept(id, name) {
        openConfirmModal({
            title: 'Do you want to delete this?',
            message: `Department "${name}" will be deleted. This action cannot be undone.`,
            icon: 'bi-trash',
            iconColor: '#dc2626',
            confirmText: 'Yes, Delete',
            confirmClass: 'btn-danger',
            onConfirm: () => document.getElementById('del-dept-' + id).submit()
        });
    }
</script>
@endpush