@extends('layouts.app')
@section('title', 'Recipe Details: ' . $recipe->name)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $recipe->name }}</h2>
                <span class="badge-status badge-status-{{ strtolower($recipe->status) }}">{{ $recipe->status }}</span>
            </div>
            <p class="page-subtitle">Recipe Code: {{ $recipe->recipe_code }} | Target Product: {{ $recipe->product->name ?? 'None' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 1]) }}" class="btn btn-primary-sattva"><i class="bi bi-pencil me-1"></i> Open Builder</a>
            <a href="{{ route('recipe.versions', $recipe) }}" class="btn btn-outline-info"><i class="bi bi-clock-history me-1"></i> Versions</a>
            <a href="{{ route('recipe.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    @php $v = $recipe->currentVersion; @endphp
    @if($v)
    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <span class="fw-bold">Active Specification Version: <span class="text-success font-monospace">v{{ $v->version_number }}</span> ({{ $v->status }})</span>
            <span class="text-muted small">Standard Batch Size: <strong>{{ $v->reference_batch_size }} {{ $v->batchUom->symbol ?? '' }}</strong></span>
        </div>
        <div class="card-sattva-body p-4">
            <h6 class="fw-bold mb-3">Configured Process Flow Stages</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">Seq</th>
                            <th>Stage Name</th>
                            <th>Activities</th>
                            <th>Monitored Parameters</th>
                            <th>Inputs / Solvents</th>
                            <th>Equipments</th>
                            <th>QC Tests</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($v->stages()->orderBy('sequence_no')->get() as $s)
                        <tr>
                            <td class="text-center fw-bold">{{ $s->sequence_no }}</td>
                            <td class="fw-semibold text-dark">{{ $s->stage->name ?? '-' }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $s->activities()->count() }}</span></td>
                            <td><span class="badge bg-light text-dark border">{{ $s->parameters()->count() }}</span></td>
                            <td><span class="badge bg-light text-dark border">{{ $s->materials()->count() }}</span></td>
                            <td><span class="badge bg-light text-dark border">{{ $s->equipments()->count() }}</span></td>
                            <td>
                                @if($s->qcTests()->count() > 0)
                                    <span class="badge bg-warning-subtle text-warning border">{{ $s->qcTests()->count() }} Tests</span>
                                @else
                                    <span class="text-muted small">None</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-3 text-muted">No stages configured in this version.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-warning">No versions configured for this recipe yet. Open the builder to initialize one.</div>
    @endif
</div>
@endsection