@extends('layouts.app')
@section('title', 'Parameter Master')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Process Parameter Master</h2>
            <p class="page-subtitle">Configure monitored process parameters (Temperature, pH, Brix, Pressure, Agitation, etc.).</p>
        </div>
        <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addParamModal">
            <i class="bi bi-plus-lg me-1"></i> Add Parameter
        </button>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('masters.parameters.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search parameter name, code..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('masters.parameters.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Parameter Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Default UOM</th>
                        <th>Standard Range</th>
                        <th>Mandatory</th>
                        <th>Status</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parameters as $idx => $param)
                    <tr>
                        <td>{{ $parameters->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $param->code }}</td>
                        <td class="fw-semibold text-dark">{{ $param->name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $param->parameter_type }}</span></td>
                        <td>{{ $param->default_uom ?? '-' }}</td>
                        <td>
                            @if($param->min_value !== null || $param->max_value !== null)
                                {{ $param->min_value ?? 0 }} - {{ $param->max_value ?? 0 }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($param->is_mandatory)
                                <span class="badge bg-danger-subtle text-danger border">Yes</span>
                            @else
                                <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('masters.parameters.toggle-status', $param) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="border-0 bg-transparent p-0" onclick="confirmAction('Toggle status for {{ $param->name }}?', this.closest('form'))">
                                    <span class="badge-status badge-status-{{ strtolower($param->status) }}">{{ $param->status }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('masters.parameters.edit', $param) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.parameters.destroy', $param) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete('Do you want to delete this Parameter?', '{{ $param->name }}', this.closest('form'))"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No parameters found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($parameters->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $parameters->firstItem() }} to {{ $parameters->lastItem() }} of {{ $parameters->total() }}</span>
            {{ $parameters->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addParamModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('masters.parameters.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Process Parameter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Parameter Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Temperature">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Parameter Code</label>
                        <input type="text" name="code" class="form-control" placeholder="Auto-generated if blank">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Parameter Type</label>
                        <select name="parameter_type" class="form-select" required>
                            <option value="Numeric">Numeric</option>
                            <option value="Text">Text</option>
                            <option value="Choice">Choice / Options</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Default UOM</label>
                        <input type="text" name="default_uom" class="form-control" placeholder="e.g. °C, RPM">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Min Limit</label>
                        <input type="number" step="0.01" name="min_value" class="form-control" placeholder="Min">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Max Limit</label>
                        <input type="number" step="0.01" name="max_value" class="form-control" placeholder="Max">
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Target Value</label>
                        <input type="text" name="target_value" class="form-control" placeholder="Target / Optimal">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="isMand">
                    <label class="form-check-label" for="isMand">Mandatory Logging in Execution</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-sattva">Save Parameter</button>
            </div>
        </form>
    </div>
</div>
@endsection