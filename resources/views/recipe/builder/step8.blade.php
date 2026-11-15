@extends('layouts.app')
@section('title', 'Recipe Builder - Step 8: Review & Activation')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> | Step 8: Formulation Audit, Approval & Version Lock</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    @if(!empty($validationErrors))
        <div class="alert alert-danger mb-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Validation Blockers (Must resolve before activation):</h6>
            <ul class="mb-0">
                @foreach($validationErrors as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><strong>Validation Passed!</strong> This recipe version is complete and ready for approval and production batch issuance.
        </div>
    @endif

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold">Version Specification Summary</span>
            <span class="badge-status badge-status-{{ strtolower($version->status) }}">{{ $version->status }}</span>
        </div>
        <div class="card-sattva-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="text-muted small">Recipe Code</label><div class="fw-bold">{{ $recipe->recipe_code }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Target Product</label><div class="fw-bold">{{ $recipe->product->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Standard Batch Size</label><div class="fw-bold">{{ $version->reference_batch_size }} {{ $version->batchUom->symbol ?? '' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Total Process Stages</label><div class="fw-bold text-success">{{ $version->stages->count() }} Stages</div></div>
            </div>

            <h6 class="fw-bold mb-3">Configured Stages Tree</h6>
            <div class="list-group mb-4">
                @foreach($version->stages()->orderBy('sequence_no')->get() as $s)
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><span class="badge bg-secondary me-2">Seq {{ $s->sequence_no }}</span> {{ $s->stage->name ?? 'Stage' }}</span>
                        <div class="d-flex gap-2">
                            <span class="badge bg-light text-dark border">{{ $s->activities->count() }} Activities</span>
                            <span class="badge bg-light text-dark border">{{ $s->parameters->count() }} Parameters</span>
                            <span class="badge bg-light text-dark border">{{ $s->materials->count() }} Materials</span>
                            <span class="badge bg-light text-dark border">{{ $s->equipments->count() }} Equipments</span>
                            <span class="badge bg-light text-dark border">{{ $s->qcTests->count() }} QC Tests</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 7]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to QC</a>
                <div class="d-flex gap-2">
                    @if($version->status === 'Draft' && empty($validationErrors))
                        <form action="{{ route('recipe.recipes.submit', $recipe) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-warning" onclick="confirmAction('Submit this recipe version for QA / Production Manager approval?', this.closest('form'))">
                                <i class="bi bi-send me-1"></i> Submit for Approval
                            </button>
                        </form>
                    @endif

                    @if(in_array($version->status, ['Draft', 'Pending Approval']) && empty($validationErrors))
                        <form action="{{ route('recipe.recipes.approve', $recipe) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-info text-white" onclick="confirmAction('Approve this recipe specification?', this.closest('form'))">
                                <i class="bi bi-check2-circle me-1"></i> Approve Version
                            </button>
                        </form>
                    @endif

                    @if($version->status !== 'Active' && empty($validationErrors))
                        <form action="{{ route('recipe.recipes.activate', $recipe) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="button" class="btn btn-primary-sattva" onclick="confirmAction('Activate this version? This will set it as the live recipe for production batch issuance and archive previous versions.', this.closest('form'))">
                                <i class="bi bi-shield-check me-1"></i> Activate & Lock Version
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('recipe.recipes.index') }}" class="btn btn-secondary">Done</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection