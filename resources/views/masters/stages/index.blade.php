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
                    <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search stage name, code..."></div>
                    <div class="col-md-2">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            @foreach(['Processing', 'Treatment', 'Separation', 'Drying'] as $cat)
                                <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
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
                            <th>Category</th>
                            <th>Default Equipment</th>
                            <th>Std Duration</th>
                            <th>QC Available</th>
                            <th>Status</th>
                            <th width="120" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stages as $idx => $stage)
                            <tr>
                                <td>{{ $stages->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $stage->stage_code }}</td>
                                <td class="fw-semibold text-dark">{{ $stage->stage_name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $stage->category }}</span></td>
                                <td>{{ $stage->defaultEquipment->name ?? '-' }}</td>
                                <td>{{ $stage->default_duration ? $stage->default_duration . ' ' . $stage->duration_uom : '-' }}</td>
                                <td>
                                    @if($stage->qc_available)
                                        <span class="badge bg-warning-subtle text-warning border"><i
                                                class="bi bi-shield-check me-1"></i>Available</span>
                                    @else
                                        <span class="text-muted small">No</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('masters.stages.toggle-status', $stage) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            data-confirm-name="{{ $stage->stage_name }}"
                                            onclick="confirmAction('Do you want to toggle status for ' + this.dataset.confirmName + '?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($stage->status) }}">{{ $stage->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.stages.edit', $stage) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.stages.destroy', $stage) }}"
                                        data-name="{{ $stage->stage_name }}" title="Delete"><i
                                            class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No stages found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($stages->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $stages->firstItem() }} to {{ $stages->lastItem() }} of
                        {{ $stages->total() }}</span>
                    {{ $stages->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addStageModal" tabindex="-1">
        <div class="modal-dialog modal-lg sattva-modal-lg">
            <form action="{{ route('masters.stages.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Manufacturing Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Stage Name</label>
                        <input type="text" name="stage_name" class="form-control" required placeholder="e.g. Hydro-Alcoholic Extraction">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Stage Code</label>
                            <input type="text" name="stage_code" class="form-control"
                                placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Category</label>
                            <select name="category" class="form-select" required>
                                <option value="Processing">Processing</option>
                                <option value="Treatment">Treatment</option>
                                <option value="Separation">Separation</option>
                                <option value="Drying">Drying</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Default Duration</label>
                            <input type="number" name="default_duration" class="form-control" min="1"
                                placeholder="e.g. 8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Duration UOM</label>
                            <select name="duration_uom" class="form-select" required>
                                <option value="Hours">Hours</option>
                                <option value="Minutes">Minutes</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Default Equipment</label>
                        <select name="default_equipment_id" class="form-select">
                            <option value="">None / Select Equipment</option>
                            @foreach($equipments as $eq)
                                <option value="{{ $eq->id }}">{{ $eq->name }} ({{ $eq->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                            placeholder="Optional process stage notes..."></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <input type="hidden" name="activity_required" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="activity_required" value="1"
                                    id="actReq" checked>
                                <label class="form-check-label" for="actReq">Activity Logging Required</label>
                            </div>
                            <input type="hidden" name="material_input_required" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="material_input_required" value="1"
                                    id="matInReq" checked>
                                <label class="form-check-label" for="matInReq">Material Input Required</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <input type="hidden" name="material_output_required" value="0">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="material_output_required" value="1"
                                    id="matOutReq" checked>
                                <label class="form-check-label" for="matOutReq">Material Output Required</label>
                            </div>
                            <input type="hidden" name="qc_available" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="qc_available" value="1"
                                    id="qcAvail" checked>
                                <label class="form-check-label" for="qcAvail">QC Inspection Available</label>
                            </div>
                        </div>
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