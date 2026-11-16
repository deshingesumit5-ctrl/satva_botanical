@extends('layouts.app')
@section('title', 'Supplier Master')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Supplier & Vendor Master</h2>
                <p class="page-subtitle">Manage approved botanical raw material collectors, solvent distributors and
                    vendors.</p>
            </div>
            <a href="{{ route('masters.suppliers.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-plus-lg me-1"></i> Add Supplier
            </a>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.suppliers.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search supplier name, code, GSTIN, city..."></div>
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
                        <a href="{{ route('masters.suppliers.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Supplier Code</th>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Mobile / Email</th>
                            <th>GSTIN</th>
                            <th>City / State</th>
                            <th>Status</th>
                            <th width="140" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $idx => $sup)
                            <tr>
                                <td>{{ $suppliers->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $sup->supplier_code }}</td>
                                <td class="fw-semibold text-dark">{{ $sup->name }}</td>
                                <td>{{ $sup->contact_person ?? '-' }}</td>
                                <td>
                                    <div>{{ $sup->mobile ?? '-' }}</div>
                                    <small class="text-muted">{{ $sup->email }}</small>
                                </td>
                                <td><code>{{ $sup->gstin ?? '-' }}</code></td>
                                <td>{{ $sup->city ?? '-' }}, {{ $sup->state ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('masters.suppliers.toggle-status', $sup) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            onclick="confirmAction('Toggle status for {{ $sup->name }}?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($sup->status) }}">{{ $sup->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.suppliers.show', $sup) }}"
                                        class="btn btn-sm btn-icon btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('masters.suppliers.edit', $sup) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.suppliers.destroy', $sup) }}" data-name="{{ $sup->name }}"
                                        title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No suppliers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($suppliers->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $suppliers->firstItem() }} to {{ $suppliers->lastItem() }} of
                        {{ $suppliers->total() }}</span>
                    {{ $suppliers->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection