@extends('layouts.app')
@section('title', 'Category Master')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4">
        <div>
            <h2 class="page-title">Category Master</h2>
            <p class="page-subtitle">Manage main product categories, display sequence and catalog thumbnails.</p>
        </div>
        <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-lg me-1"></i> Add New Category
        </button>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('masters.categories.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-5">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search category name...">
                </div>
                <div class="col-6 col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="per_page" class="form-select">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10 / page</option>
                        <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25 / page</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50 / page</option>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('masters.categories.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Category Name</th>
                        <th>Display Order</th>
                        <th>Total Products</th>
                        <th>Status</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $idx => $category)
                    <tr>
                        <td>{{ $categories->firstItem() + $idx }}</td>
                        <td class="fw-semibold text-dark">{{ $category->name }}</td>
                        <td><span class="badge bg-light text-dark border px-2 py-1">{{ $category->display_order }}</span></td>
                        <td>{{ $category->products_count ?? 0 }} Products</td>
                        <td>
                            <form action="{{ route('masters.categories.toggle-status', $category) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="border-0 bg-transparent p-0" onclick="confirmAction('Toggle status for {{ $category->name }}?', this.closest('form'))">
                                    <span class="badge-status badge-status-{{ strtolower($category->status) }}">{{ $category->status }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('masters.categories.edit', $category) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.categories.destroy', $category) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete('Do you want to delete this Category?', '{{ $category->name }}', this.closest('form'))"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No categories found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($categories->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }}</span>
            {{ $categories->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('masters.categories.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">CATEGORY NAME *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Standardized Herbal Extracts">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">DISPLAY ORDER</label>
                        <input type="number" name="display_order" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">STATUS</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-sattva">Save Category</button>
            </div>
        </form>
    </div>
</div>
@endsection