@extends('layouts.app')
@section('title', 'Recipe Builder - Step 1: Basic Info')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> ({{ $recipe->recipe_code }}) | Version: <span class="badge bg-light text-dark border">v{{ $version->version_number }} ({{ $version->status }})</span></p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <div class="card card-sattva" style="max-width: 800px;">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Step 1: General Batch Formulation</h6></div>
        <div class="card-sattva-body p-4">
            @if($isReadOnly)
                <div class="alert alert-warning mb-4"><i class="bi bi-lock me-1"></i> This version is <strong>{{ $version->status }}</strong> and is locked against modifications.</div>
            @endif

            <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 1]) }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Recipe Code</label>
                        <input type="text" class="form-control" value="{{ $recipe->recipe_code }}" disabled>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Recipe Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $recipe->name) }}" {{ $isReadOnly ? 'disabled' : 'required' }}>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Finished Good Product</label>
                        <input type="text" class="form-control" value="{{ $recipe->product->name ?? '-' }}" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Ref Batch Size</label>
                        <input type="number" step="0.01" name="reference_batch_size" class="form-control" value="{{ old('reference_batch_size', $version->reference_batch_size) }}" {{ $isReadOnly ? 'disabled' : 'required' }}>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Batch Unit</label>
                        <select name="batch_uom_id" class="form-select" {{ $isReadOnly ? 'disabled' : 'required' }}>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}" {{ $version->batch_uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Description / Processing Methodology</label>
                    <textarea name="description" class="form-control" rows="3" {{ $isReadOnly ? 'disabled' : '' }}>{{ old('description', $version->description) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    @if(!$isReadOnly)
                        <button type="submit" class="btn btn-primary-sattva">Save & Proceed to Stage Flow <i class="bi bi-arrow-right ms-1"></i></button>
                    @else
                        <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 2]) }}" class="btn btn-primary-sattva">Next Step <i class="bi bi-arrow-right ms-1"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection