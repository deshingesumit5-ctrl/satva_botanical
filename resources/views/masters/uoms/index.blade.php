@extends('layouts.app')
@section('title', 'Unit of Measurement (UOM) Master')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Unit of Measurement (UOM)</h2>
            <p class="page-subtitle">Configure standard units, symbols, decimal precision and stock measurement flags.</p>
        </div>
        <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addUomModal">
            <i class="bi bi-plus-lg me-1"></i> Add UOM
        </button>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('masters.uoms.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search UOM name, symbol..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('masters.uoms.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>UOM Name</th>
                        <th>Symbol</th>
                        <th>Decimals</th>
                        <th>Stock Unit</th>
                        <th>Status</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($uoms as $idx => $uom)
                    <tr>
                        <td>{{ $uoms->firstItem() + $idx }}</td>
                        <td class="fw-semibold text-dark">{{ $uom->name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $uom->symbol }}</span></td>
                        <td>{{ $uom->decimal_places ?? 2 }}</td>
                        <td>
                            @if($uom->is_stock_unit)
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Yes</span>
                            @else
                                <span class="badge bg-light text-muted border">No</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('masters.uoms.toggle-status', $uom) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="border-0 bg-transparent p-0" onclick="confirmAction('Do you want to toggle status for {{ $uom->name }}?', this.closest('form'))">
                                    <span class="badge-status badge-status-{{ strtolower($uom->status) }}">{{ $uom->status }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('masters.uoms.edit', $uom) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.uoms.destroy', $uom) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete('Do you want to delete this UOM?', '{{ $uom->name }}', this.closest('form'))"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No UOM records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($uoms->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $uoms->firstItem() }} to {{ $uoms->lastItem() }} of {{ $uoms->total() }}</span>
            {{ $uoms->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addUomModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('masters.uoms.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New UOM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">UOM Name</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Kilogram">
                </div>
                <div class="mb-3">
                    <label class="form-label required">Symbol / Abbreviation</label>
                    <input type="text" name="symbol" class="form-control" required placeholder="e.g. kg">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Decimal Places</label>
                        <input type="number" name="decimal_places" class="form-control" value="2" min="0" max="6">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_stock_unit" value="1" id="isStockUnit" checked>
                    <label class="form-check-label" for="isStockUnit">Allow as Primary Stock Unit</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-sattva">Save UOM</button>
            </div>
        </form>
    </div>
</div>
@endsection