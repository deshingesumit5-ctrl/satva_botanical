@extends('layouts.app')
@section('title', 'QC Test Master')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">QC Test Master</h2>
                <p class="page-subtitle">Configure laboratory quality control tests (Assay, Moisture, Heavy Metals,
                    Microbio, etc.).</p>
            </div>
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addQcTestModal">
                <i class="bi bi-plus-lg me-1"></i> Add QC Test
            </button>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.qc-tests.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search test name, code, method..."></div>
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
                        <a href="{{ route('masters.qc-tests.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Test Code</th>
                            <th>Test Name</th>
                            <th>Test Type</th>
                            <th>Standard Specification</th>
                            <th>Test Method</th>
                            <th>Status</th>
                            <th width="120" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($qcTests as $idx => $test)
                            <tr>
                                <td>{{ $qcTests->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $test->code }}</td>
                                <td class="fw-semibold text-dark">{{ $test->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $test->test_type }}</span></td>
                                <td>
                                    @if($test->standard_min !== null || $test->standard_max !== null)
                                        {{ $test->standard_min ?? 'Min' }} to {{ $test->standard_max ?? 'Max' }}
                                    @else
                                        <span class="text-muted">Descriptive</span>
                                    @endif
                                </td>
                                <td>{{ $test->test_method ?? 'In-House' }}</td>
                                <td>
                                    <form action="{{ route('masters.qc-tests.toggle-status', $test) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            onclick="confirmAction('Toggle status for {{ $test->name }}?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($test->status) }}">{{ $test->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.qc-tests.edit', $test) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.qc-tests.destroy', $test) }}"
                                        data-name="{{ $test->test_name ?? $test->name }}" title="Delete"><i
                                            class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No QC tests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($qcTests->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $qcTests->firstItem() }} to {{ $qcTests->lastItem() }} of
                        {{ $qcTests->total() }}</span>
                    {{ $qcTests->links() }}
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="addQcTestModal" tabindex="-1">
        <div class="modal-dialog modal-lg sattva-modal-lg">
            <form action="{{ route('masters.qc-tests.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add QC Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Test Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. HPLC Assay (% HCA)">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Test Code</label>
                            <input type="text" name="code" class="form-control" placeholder="Auto-generated if blank">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Test Type</label>
                            <select name="test_type" class="form-select" required>
                                <option value="Chemical">Chemical</option>
                                <option value="Microbiological">Microbiological</option>
                                <option value="Physical">Physical</option>
                                <option value="Instrumental">Instrumental</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Standard Min</label>
                            <input type="number" step="0.001" name="standard_min" class="form-control"
                                placeholder="Min limit">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Standard Max</label>
                            <input type="number" step="0.001" name="standard_max" class="form-control"
                                placeholder="Max limit">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Test Method / Standard</label>
                            <input type="text" name="test_method" class="form-control"
                                placeholder="e.g. IP / USP / In-house">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-sattva">Save QC Test</button>
                </div>
            </form>
        </div>
    </div>
@endsection