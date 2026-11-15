@extends('layouts.app')
@section('title', 'Recipe Builder - Step 6: Equipment')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> | Step 6: Equipment & Machinery Allocation</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 6]) }}" method="POST">
        @csrf
        @foreach($recipeStages as $rs)
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-success"><span class="badge bg-light text-dark border me-2">Seq {{ $rs->sequence_no }}</span> {{ $rs->stage->name ?? 'Stage' }}</h6>
            </div>
            <div class="card-sattva-body p-3">
                <label class="form-label small text-muted">Select Eligible Equipment for this Stage:</label>
                <div class="row g-2">
                    @php
                        $assignedIds = $rs->equipments->pluck('equipment_id')->toArray();
                    @endphp
                    @foreach($equipments as $eq)
                    <div class="col-md-4">
                        <div class="form-check p-2 border rounded bg-light">
                            <input class="form-check-input ms-1" type="checkbox" name="equipments[{{ $rs->id }}][]" value="{{ $eq->id }}" id="eq_{{ $rs->id }}_{{ $eq->id }}" {{ in_array($eq->id, $assignedIds) ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}>
                            <label class="form-check-label ms-2" for="eq_{{ $rs->id }}_{{ $eq->id }}">
                                <strong>{{ $eq->name }}</strong>
                                <small class="text-muted d-block">{{ $eq->equipment_type }} | {{ $eq->capacity }} {{ $eq->capacity_uom }}</small>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 5]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @if(!$isReadOnly)
                <button type="submit" class="btn btn-primary-sattva">Save & Proceed to QC Tests <i class="bi bi-arrow-right ms-1"></i></button>
            @else
                <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 7]) }}" class="btn btn-primary-sattva">Next Step <i class="bi bi-arrow-right ms-1"></i></a>
            @endif
        </div>
    </form>
</div>
@endsection