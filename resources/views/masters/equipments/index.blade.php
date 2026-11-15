@extends('layouts.app')
@section('title', 'Equipment Master')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Equipment & Machinery Master</h2>
            <p class="page-subtitle">Track reactors, centrifuges, dryers, boilers and calibration maintenance schedules.</p>
        </div>
        <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addEquipmentModal">
            <i class="bi bi-plus-lg me-1"></i> Add Equipment
        </button>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('masters.equipments.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search equipment name, code, type..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Maintenance" {{ request('status') === 'Maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('masters.equipments.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Equipment Code</th>
                        <th>Equipment Name</th>
                        <th>Type</th>
                        <th>Plant</th>
                        <th>Capacity</th>
                        <th>Calibration Due</th>
                        <th>Status</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipments as $idx => $eq)
                    <tr>
                        <td>{{ $equipments->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $eq->code }}</td>
                        <td class="fw-semibold text-dark">{{ $eq->name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $eq->equipment_type }}</span></td>
                        <td>{{ $eq->plant->name ?? '-' }}</td>
                        <td>{{ $eq->capacity ? $eq->capacity.' '.$eq->capacity_uom : '-' }}</td>
                        <td>
                            @if($eq->calibration_due_date)
                                <span class="{{ \Carbon\Carbon::parse($eq->calibration_due_date)->isPast() ? 'text-danger fw-bold' : 'text-dark' }}">
                                    {{ \Carbon\Carbon::parse($eq->calibration_due_date)->format('d-m-Y') }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge-status badge-status-{{ strtolower($eq->status) }}">{{ $eq->status }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('masters.equipments.edit', $eq) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.equipments.destroy', $eq) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete('Do you want to delete this Equipment?', '{{ $eq->name }}', this.closest('form'))"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No equipment found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($equipments->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $equipments->firstItem() }} to {{ $equipments->lastItem() }} of {{ $equipments->total() }}</span>
            {{ $equipments->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addEquipmentModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('masters.equipments.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Equipment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Equipment Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Extraction Vessel #1">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Equipment Code</label>
                        <input type="text" name="code" class="form-control" placeholder="Auto-generated if blank">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Equipment Type</label>
                        <input type="text" name="equipment_type" class="form-control" required placeholder="e.g. Reactor, Dryer">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Plant Facility</label>
                        <select name="plant_id" class="form-select">
                            <option value="">Select Plant</option>
                            @foreach(\App\Models\Plant::where('status', 'Active')->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Capacity</label>
                        <input type="number" step="0.01" name="capacity" class="form-control" placeholder="e.g. 5000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Capacity UOM</label>
                        <input type="text" name="capacity_uom" class="form-control" placeholder="e.g. Litres, KG">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Next Calibration Due Date</label>
                    <input type="date" name="calibration_due_date" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-sattva">Save Equipment</button>
            </div>
        </form>
    </div>
</div>
@endsection