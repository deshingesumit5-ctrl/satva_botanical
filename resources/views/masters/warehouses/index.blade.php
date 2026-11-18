@extends('layouts.app')
@section('title', 'Warehouse Master')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Warehouse Master</h2>
                <p class="page-subtitle">Configure Raw Material, Chemical, WIP, Finished Goods, Recovery and Waste
                    Warehouses.</p>
            </div>
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addWhModal"><i
                    class="bi bi-plus-lg me-1"></i> Add Warehouse</button>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.warehouses.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search warehouse name, code..."></div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            @foreach(['Raw Material', 'Chemical', 'WIP', 'Finished Goods', 'Recovery', 'Waste', 'Rejected'] as $t)
                                <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                        <a href="{{ route('masters.warehouses.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-sattva">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Warehouse Name</th>
                            <th>Type</th>
                            <th>Storage Condition</th>
                            <th>Plant</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouses as $w)
                            <tr>
                                <td class="fw-bold">{{ $w->code }}</td>
                                <td>{{ $w->name }}</td>
                                <td><span
                                        class="badge bg-secondary-subtle text-secondary-emphasis px-2 py-1 rounded-pill">{{ $w->type }}</span>
                                </td>
                                <td><small class="text-muted">{{ $w->storage_condition ?? 'Ambient' }}</small></td>
                                <td>{{ $w->plant ? $w->plant->name : '-' }}</td>
                                <td><x-status-badge :status="$w->status" /></td>
                                <td class="text-end">
                                    <a href="{{ route('masters.warehouses.edit', $w) }}"
                                        class="btn btn-sm btn-icon btn-action-edit me-1" title="Edit"><i
                                            class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.warehouses.destroy', $w) }}" data-name="{{ $w->name }}"
                                        title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No warehouses configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($warehouses->hasPages())
            <div class="p-3 border-top">{{ $warehouses->links() }}</div>@endif
        </div>
    </div>

    <div class="modal fade" id="addWhModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg sattva-modal-lg">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Warehouse</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('masters.warehouses.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label small fw-semibold">Warehouse Code *</label><input
                                type="text" name="code" class="form-control" placeholder="e.g. WH-RM" required></div>
                        <div class="mb-3"><label class="form-label small fw-semibold">Warehouse Name *</label><input
                                type="text" name="name" class="form-control" placeholder="e.g. Raw Material Store" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Type *</label>
                                <select name="type" class="form-select" required>
                                    @foreach(['Raw Material', 'Chemical', 'WIP', 'Finished Goods', 'Recovery', 'Waste', 'Rejected'] as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Plant</label>
                                <select name="plant_id" class="form-select">
                                    <option value="">Select Plant</option>
                                    @foreach($plants as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label small fw-semibold">Storage Condition</label><input
                                type="text" name="storage_condition" class="form-control"
                                placeholder="e.g. Ambient dry, Controlled temperature"></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="Active"
                                id="whActive" checked><label class="form-check-label fw-semibold"
                                for="whActive">Active</label></div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button><button type="submit"
                            class="btn btn-primary-sattva">Save</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editWhModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg sattva-modal-lg">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Warehouse</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <form id="editWhForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label small fw-semibold">Warehouse Name *</label><input
                                type="text" name="name" id="ewName" class="form-control" required></div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Type *</label>
                                <select name="type" id="ewType" class="form-select" required>
                                    @foreach(['Raw Material', 'Chemical', 'WIP', 'Finished Goods', 'Recovery', 'Waste', 'Rejected'] as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Plant</label>
                                <select name="plant_id" id="ewPlant" class="form-select">
                                    <option value="">Select Plant</option>
                                    @foreach($plants as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                        <div class="mb-3"><label class="form-label small fw-semibold">Storage Condition</label><input
                                type="text" name="storage_condition" id="ewCond" class="form-control"></div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="Active"
                                id="ewStatus"><label class="form-check-label fw-semibold" for="ewStatus">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary-sattva"
                            onclick="confirmUpdateWh()">Save Changes</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function editWh(id) {
            fetch(`/masters/warehouses/${id}/edit`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('editWhForm').action = `/masters/warehouses/${id}`;
                    document.getElementById('ewName').value = data.name;
                    document.getElementById('ewType').value = data.type;
                    document.getElementById('ewPlant').value = data.plant_id || '';
                    document.getElementById('ewCond').value = data.storage_condition || '';
                    document.getElementById('ewStatus').checked = (data.status === 'Active');
                    new bootstrap.Modal(document.getElementById('editWhModal')).show();
                });
        }
        function confirmUpdateWh() {
            openConfirmModal({
                title: 'Do you want to update this?',
                message: 'Save changes to this warehouse record.',
                icon: 'bi-pencil-square',
                iconColor: '#059669',
                confirmText: 'Yes, Update',
                confirmClass: 'btn-success',
                onConfirm: () => document.getElementById('editWhForm').submit()
            });
        }
        function deleteWh(id, name) {
            openConfirmModal({
                title: 'Do you want to delete this?',
                message: `Warehouse "${name}" will be deleted. This action cannot be undone.`,
                icon: 'bi-trash',
                iconColor: '#dc2626',
                confirmText: 'Yes, Delete',
                confirmClass: 'btn-danger',
                onConfirm: () => document.getElementById('del-wh-' + id).submit()
            });
        }
    </script>
@endpush