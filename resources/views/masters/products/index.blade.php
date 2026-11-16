@extends('layouts.app')
@section('title', 'Product Master')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Finished Goods & Product Master</h2>
                <p class="page-subtitle">Botanical extracts, specifications, shelf lives, HSN and batch tracking policies.
                </p>
            </div>
            <a href="{{ route('masters.products.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-plus-lg me-1"></i> Add Product
            </a>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('masters.products.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search product name, code, HSN..."></div>
                    <div class="col-md-3">
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}</option>
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
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                        <a href="{{ route('masters.products.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Product Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Base UOM</th>
                            <th>HSN</th>
                            <th>Shelf Life</th>
                            <th>Status</th>
                            <th width="140" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $idx => $prod)
                            <tr>
                                <td>{{ $products->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $prod->product_code }}</td>
                                <td class="fw-semibold text-dark">{{ $prod->name }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $prod->category->name ?? '-' }}</span></td>
                                <td>{{ $prod->baseUom->symbol ?? '-' }}</td>
                                <td>{{ $prod->hsn_code ?? '-' }}</td>
                                <td>{{ $prod->shelf_life ? $prod->shelf_life . ' months' : '-' }}</td>
                                <td>
                                    <form action="{{ route('masters.products.toggle-status', $prod) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            onclick="confirmAction('Toggle status for {{ $prod->name }}?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($prod->status) }}">{{ $prod->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('masters.products.show', $prod) }}"
                                        class="btn btn-sm btn-icon btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('masters.products.edit', $prod) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('masters.products.destroy', $prod) }}" data-name="{{ $prod->name }}"
                                        title="Delete"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No products found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($products->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of
                        {{ $products->total() }}</span>
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection