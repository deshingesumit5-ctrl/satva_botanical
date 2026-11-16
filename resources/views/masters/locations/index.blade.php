@extends('layouts.app')
@section('title', 'Storage Locations & Bins')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Storage Locations & Bins</h2>
                <p class="page-subtitle">Configure internal racks, pallets and bin storage locations.</p>
            </div>
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addLocModal"><i
                    class="bi bi-plus-lg me-1"></i> Add Location</button>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.locations.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search location code, name, rack, bin..."></div>
                    <div class="col-md-3">
                        <select name="warehouse_id" class="form-select">
                            <option value="">All Warehouses</option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" {{ request('warehouse_id') == $w->id ? 'selected' : '' }}>
                            {{ $w->name }}</option>@endforeach
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
                        <a href="{{ route('masters.locations.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-sattva">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Location Code</th>
                            <th>Name</th>
                            <th>Warehouse</th>
                            <th>Rack No</th>
                            <th>Bin No</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $l)
                            <tr>
                                <td class="fw-bold">{{ $l->location_code }}</td>
                                <td>{{ $l->name }}</td>
                                <td><span
                                        class="badge bg-light text-dark border">{{ $l->warehouse ? $l->warehouse->name : 'N/A' }}</span>
                                </td>
                                <td>{{ $l->rack_no ?? '-' }}</td>
                                <td>{{ $l->bin_no ?? '-' }}</td>
                                <td><x-status-badge :status="$l->status" /></td>
                                <td class="text-end">
                                    <a href="{{ route('masters.locations.edit', $l) }}"
                                        class="btn btn-sm btn-icon btn-action-edit me-1" title="Edit"><i
                                            class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.locations.destroy', $l) }}" data-name="{{ $l->name }}"
                                        title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No storage locations configured.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($locations->hasPages())
            <div class="p-3 border-top">{{ $locations->links() }}</div>@endif
        </div>
    </div>

    <div class="modal fade" id="addLocModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Storage Location</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('masters.locations.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label small fw-semibold">Location Code *</label><input
                                type="text" name="location_code" class="form-control" placeholder="e.g. LOC-RM-R1-B1"
                                required></div>
                        <div class="mb-3"><label class="form-label small fw-semibold">Location Name *</label><input
                                type="text" name="name" class="form-control" placeholder="e.g. Rack A Bin 01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Warehouse *</label>
                            <select name="warehouse_id" class="form-select" required>
                                <option value="">Select Warehouse</option>
                                @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="form-label small fw-semibold">Rack No</label><input type="text"
                                    name="rack_no" class="form-control" placeholder="RACK-1"></div>
                            <div class="col-6"><label class="form-label small fw-semibold">Bin No</label><input type="text"
                                    name="bin_no" class="form-control" placeholder="BIN-01"></div>
                        </div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="Active"
                                id="locActive" checked><label class="form-check-label fw-semibold"
                                for="locActive">Active</label></div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button><button type="submit"
                            class="btn btn-primary-sattva">Save</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editLocModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Location</h5><button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                </div>
                <form id="editLocForm" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3"><label class="form-label small fw-semibold">Location Name *</label><input
                                type="text" name="name" id="elName" class="form-control" required></div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Warehouse *</label>
                            <select name="warehouse_id" id="elWh" class="form-select" required>
                                @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6"><label class="form-label small fw-semibold">Rack No</label><input type="text"
                                    name="rack_no" id="elRack" class="form-control"></div>
                            <div class="col-6"><label class="form-label small fw-semibold">Bin No</label><input type="text"
                                    name="bin_no" id="elBin" class="form-control"></div>
                        </div>
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="status" value="Active"
                                id="elStatus"><label class="form-check-label fw-semibold" for="elStatus">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary-sattva"
                            onclick="confirmUpdateLoc()">Save Changes</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function editLoc(id) {
            fetch(`/masters/locations/${id}/edit`, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(data => {
                    document.getElementById('editLocForm').action = `/masters/locations/${id}`;
                    document.getElementById('elName').value = data.name;
                    document.getElementById('elWh').value = data.warehouse_id;
                    document.getElementById('elRack').value = data.rack_no || '';
                    document.getElementById('elBin').value = data.bin_no || '';
                    document.getElementById('elStatus').checked = (data.status === 'Active');
                    new bootstrap.Modal(document.getElementById('editLocModal')).show();
                });
        }
        function confirmUpdateLoc() {
            openConfirmModal({
                title: 'Do you want to update this?',
                message: 'Save changes to this storage location.',
                icon: 'bi-pencil-square',
                iconColor: '#059669',
                confirmText: 'Yes, Update',
                confirmClass: 'btn-success',
                onConfirm: () => document.getElementById('editLocForm').submit()
            });
        }
        function deleteLoc(id, name) {
            openConfirmModal({
                title: 'Do you want to delete this?',
                message: `Storage location "${name}" will be deleted. This action cannot be undone.`,
                icon: 'bi-trash',
                iconColor: '#dc2626',
                confirmText: 'Yes, Delete',
                confirmClass: 'btn-danger',
                onConfirm: () => document.getElementById('del-loc-' + id).submit()
            });
        }
    </script>
@endpush