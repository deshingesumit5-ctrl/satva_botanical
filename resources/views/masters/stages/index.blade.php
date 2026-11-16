@extends('layouts.app')
@section('title', 'Stage Master')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Manufacturing Stage Master</h2>
                <p class="page-subtitle">Configure generic process stages (Extraction, Distillation, Crystallization, etc.).
                </p>
            </div>
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addStageModal">
                <i class="bi bi-plus-lg me-1"></i> Add Stage
            </button>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.stages.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search stage name, code..."></div>
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
                        <a href="{{ route('masters.stages.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Stage Code</th>
                        <th>Stage Name</th>
                        <th>Default Seq</th>
                        <th>Std Duration</th>
                        <th>Recovery?</th>
                        <th>QC Mandatory</th>
                        <th>Status</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stages as $idx => $stage)
                    <tr>
                        <td>{{ $stages->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $stage->code }}</td>
                        <td class="fw-semibold text-dark">{{ $stage->name }}</td>
                        <td>{{ $stage->default_sequence ?? '-' }}</td>
                        <td>{{ $stage->standard_duration_hours ? $stage->standard_duration_hours.' hrs' : '-' }}</td>
                        <td>
                            @if($stage->recovery_applicable)
                                <span class="badge bg-info-subtle text-info border">Applicable</span>
                            @else
                                <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            @if($stage->qc_mandatory_on_exit)
                                <span class="badge bg-warning-subtle text-warning border"><i class="bi bi-shield-check me-1"></i>Mandatory</span>
                            @else
                                <span class="text-muted small">Optional</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('masters.stages.toggle-status', $stage) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="border-0 bg-transparent p-0" onclick="confirmAction('Do you want to toggle status for {{ $stage->name }}?', this.closest('form'))">
                                    <span class="badge-status badge-status-{{ strtolower($stage->status) }}">{{ $stage->status }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('masters.stages.edit', $stage) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <button type="button" class="btn btn-sm btn-icon btn-action-delete" data-url="{{ route('masters.stages.destroy', $stage) }}" data-name="{{ $stage->stage_name ?? $stage->name }}" title="Delete"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No stages found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="addStageModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('masters.stages.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Manufacturing Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Stage Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Extraction">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Stage Code</label>
                            <input type="text" name="code" class="form-control" placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Sequence</label>
                            <input type="number" name="default_sequence" class="form-control" value="1" min="1">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Standard Duration (Hrs)</label>
                            <input type="number" step="0.1" name="standard_duration_hours" class="form-control"
                                placeholder="e.g. 8.0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="recovery_applicable" value="1" id="recApp">
                        <label class="form-check-label" for="recApp">Solvent Recovery Applicable</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="qc_mandatory_on_exit" value="1" id="qcMand">
                        <label class="form-check-label" for="qcMand">QC Inspection Mandatory On Stage Exit</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-sattva">Save Stage</button>
                </div>
            </form>
        </div>
    </div>
@endsection