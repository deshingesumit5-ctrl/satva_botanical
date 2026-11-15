@extends('layouts.app')
@section('title', 'Edit Category')
@section('content')
<div class="container-fluid px-0" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Category</h2>
            <p class="page-subtitle">Update category details and display sequence.</p>
        </div>
        <a href="{{ route('masters.categories.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.categories.update', $category) }}" method="POST">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label required">Category Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="{{ old('display_order', $category->display_order) }}" min="1">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $category->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $category->status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.categories.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Category?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection