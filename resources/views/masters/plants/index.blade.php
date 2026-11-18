@extends('layouts.app')
@section('title', 'Plant Master')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Company & Plant Master</h2>
                <p class="page-subtitle">Configure manufacturing facilities, GSTIN and physical site locations.</p>
            </div>
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addPlantModal">
                <i class="bi bi-plus-lg me-1"></i> Add Plant
            </button>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.plants.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search plant name, code, GSTIN..."></div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                        <a href="{{ route('masters.plants.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Plant Code</th>
                            <th>Plant Name</th>
                            <th>GSTIN</th>
                            <th>Address / City</th>
                            <th>Status</th>
                            <th width="120" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plants as $idx => $plant)
                            <tr>
                                <td>{{ $plants->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $plant->code }}</td>
                                <td class="fw-semibold text-dark">{{ $plant->name }}</td>
                                <td><code>{{ $plant->gstin ?? '-' }}</code></td>
                                <td>{{ $plant->address ? Str::limit($plant->address, 40) : '-' }}</td>
                                <td>
                                    <form action="{{ route('masters.plants.toggle-status', $plant) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            data-confirm-name="{{ $plant->name }}"
                                            onclick="confirmAction('Toggle status for ' + this.dataset.confirmName + '?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($plant->status) }}">{{ $plant->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.plants.edit', $plant) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.plants.destroy', $plant) }}" data-name="{{ $plant->name }}"
                                        title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No plants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($plants->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $plants->firstItem() }} to {{ $plants->lastItem() }} of
                        {{ $plants->total() }}</span>
                    {{ $plants->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addPlantModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('masters.plants.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Plant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Plant Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Sattva Unit 1">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Plant Code</label>
                            <input type="text" name="code" class="form-control" placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin" class="form-control" placeholder="27ABCDE1234F1Z5">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"
                            placeholder="Plant physical location..."></textarea>
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
                    <button type="submit" class="btn btn-primary-sattva">Save Plant</button>
                </div>
            </form>
        </div>
    </div>
@endsection