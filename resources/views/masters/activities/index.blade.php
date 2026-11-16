@extends('layouts.app')
@section('title', 'Activity Master')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Activity Master</h2>
                <p class="page-subtitle">Configure operational activities performed within stages (Charging, Heating,
                    Stirring, etc.).</p>
            </div>
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                <i class="bi bi-plus-lg me-1"></i> Add Activity
            </button>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.activities.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search activity name, code..."></div>
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
                        <a href="{{ route('masters.activities.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Activity Code</th>
                            <th>Activity Name</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th width="120" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $idx => $activity)
                            <tr>
                                <td>{{ $activities->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $activity->code }}</td>
                                <td class="fw-semibold text-dark">{{ $activity->name }}</td>
                                <td class="text-muted small">{{ Str::limit($activity->description, 50) ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('masters.activities.toggle-status', $activity) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            onclick="confirmAction('Toggle status for {{ $activity->name }}?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($activity->status) }}">{{ $activity->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.activities.edit', $activity) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.activities.destroy', $activity) }}"
                                        data-name="{{ $activity->name }}" title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No activities found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($activities->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $activities->firstItem() }} to {{ $activities->lastItem() }} of
                        {{ $activities->total() }}</span>
                    {{ $activities->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addActivityModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('masters.activities.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Activity Name</label>
                        <input type="text" name="name" class="form-control" required
                            placeholder="e.g. Charging Raw Material">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Activity Code</label>
                            <input type="text" name="code" class="form-control" placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description / Instructions</label>
                        <textarea name="description" class="form-control" rows="3"
                            placeholder="Operational instructions for operators..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-sattva">Save Activity</button>
                </div>
            </form>
        </div>
    </div>
@endsection