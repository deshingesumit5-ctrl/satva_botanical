@extends('layouts.app')
@section('title', 'Create Recipe')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">New Master Recipe</h2>
            <p class="page-subtitle">Initialize a master formulation and open the 8-step recipe builder.</p>
        </div>
        <a href="{{ route('recipe.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('recipe.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Recipe Code</label>
                        <input type="text" name="recipe_code" class="form-control" required placeholder="e.g. REC-GARC-60">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Recipe Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Garcinia Cambogia Standardized Extract 60%">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Target Finished Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Finished Product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->product_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Reference Batch Size</label>
                        <input type="number" step="0.01" name="reference_batch_size" class="form-control" required placeholder="e.g. 1000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Batch UOM</label>
                        <select name="batch_uom_id" class="form-select" required>
                            <option value="">Select UOM</option>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Initial Version Notes / Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Overview of extraction route, solvent system, target purity..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('recipe.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Create & Proceed to Builder <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection