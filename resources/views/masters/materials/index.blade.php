@extends('layouts.app')
@section('title', 'Material Master')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Raw Materials & Solvents Master</h2>
            <p class="page-subtitle">Configure botanical herbs, extraction solvents, packaging materials and chemicals.</p>
        </div>
        <a href="{{ route('masters.materials.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Add Material
        </a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('masters.materials.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search material name, code, HSN..."></div>
                <div class="col-md-3">
                    <select name="material_type" class="form-select">
                        <option value="">All Material Types</option>
                        <option value="Raw Material" {{ request('material_type') === 'Raw Material' ? 'selected' : '' }}>Raw Material (Herbs)</option>
                        <option value="Solvent" {{ request('material_type') === 'Solvent' ? 'selected' : '' }}>Solvent (Methanol/Water/etc.)</option>
                        <option value="Packaging" {{ request('material_type') === 'Packaging' ? 'selected' : '' }}>Packaging Material</option>
                        <option value="Chemical" {{ request('material_type') === 'Chemical' ? 'selected' : '' }}>Chemical / Reagent</option>
                        <option value="Consumable" {{ request('material_type') === 'Consumable' ? 'selected' : '' }}>Consumable</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('masters.materials.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Material Code</th>
                        <th>Material Name</th>
                        <th>Type</th>
                        <th>Base UOM</th>
                        <th>Min Stock</th>
                        <th>Reorder Level</th>
                        <th>Hazardous?</th>
                        <th>Status</th>
                        <th width="140" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materials as $idx => $mat)
                    <tr>
                        <td>{{ $materials->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $mat->material_code }}</td>
                        <td class="fw-semibold text-dark">{{ $mat->name }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $mat->material_type }}</span></td>
                        <td>{{ $mat->baseUom->symbol ?? '-' }}</td>
                        <td>{{ $mat->min_stock ?? 0 }}</td>
                        <td>{{ $mat->reorder_level ?? 0 }}</td>
                        <td>
                            @if($mat->is_hazardous)
                                <span class="badge bg-danger-subtle text-danger border"><i class="bi bi-exclamation-triangle me-1"></i>Hazardous</span>
                            @else
                                <span class="text-muted small">No</span>
                            @endif
                        </td>
                        <td>
                            <form action="{{ route('masters.materials.toggle-status', $mat) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="border-0 bg-transparent p-0" onclick="confirmAction('Toggle status for {{ $mat->name }}?', this.closest('form'))">
                                    <span class="badge-status badge-status-{{ strtolower($mat->status) }}">{{ $mat->status }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('masters.materials.show', $mat) }}" class="btn btn-sm btn-icon btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.materials.edit', $mat) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.materials.destroy', $mat) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete('Do you want to delete this Material?', '{{ $mat->name }}', this.closest('form'))"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center py-4 text-muted">No materials found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($materials->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $materials->firstItem() }} to {{ $materials->lastItem() }} of {{ $materials->total() }}</span>
            {{ $materials->links() }}
        </div>
        @endif
    </div>
</div>
@endsection