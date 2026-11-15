@extends('layouts.app')
@section('title', 'Recipe Builder - Step 2: Stage Flow')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> ({{ $recipe->recipe_code }}) | Step 2: Process Stage Flow Builder</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold">Process Flow Sequence Builder</h6>
                <small class="text-muted">Drag & drop items using the handle icon <i class="bi bi-grip-vertical"></i> to reorder. Stages can be repeated (e.g. Wash 1, Wash 2, Extraction 1, Extraction 2).</small>
            </div>
            @if(!$isReadOnly)
            <div class="d-flex gap-2">
                <select id="stagePicker" class="form-select form-select-sm" style="width: 250px;">
                    <option value="">-- Add Process Stage --</option>
                    @foreach($stages as $st)
                        <option value="{{ $st->id }}" data-name="{{ $st->name }}">{{ $st->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-primary-sattva" onclick="addStageToFlow()"><i class="bi bi-plus-lg me-1"></i> Add Stage</button>
            </div>
            @endif
        </div>
        <div class="card-sattva-body p-4">
            <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 2]) }}" method="POST" id="stageFlowForm">
                @csrf
                <div id="stagesContainer" class="list-group mb-4">
                    @forelse($recipeStages as $idx => $rs)
                    <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 stage-row border rounded mb-2 bg-white" data-id="{{ $rs->stage_id }}">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-grip-vertical fs-4 text-muted cursor-grab drag-handle"></i>
                            <span class="badge bg-secondary sequence-badge rounded-pill px-3 py-2">Seq {{ $rs->sequence_no }}</span>
                            <div>
                                <h6 class="mb-0 fw-bold stage-title">{{ $rs->stage->name ?? 'Stage' }}</h6>
                                <small class="text-muted">Occurrence #{{ $rs->occurrence_no }}</small>
                            </div>
                            <input type="hidden" name="stages[]" value="{{ $rs->stage_id }}">
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" name="qc_required[{{ $idx }}]" value="1" {{ $rs->qc_required ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}>
                                <label class="form-check-label small">Mandatory QC</label>
                            </div>
                            <input type="text" name="instructions[]" class="form-control form-control-sm" placeholder="Stage instructions..." value="{{ $rs->instructions }}" style="width: 250px;" {{ $isReadOnly ? 'disabled' : '' }}>
                            @if(!$isReadOnly)
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeStage(this)"><i class="bi bi-trash"></i></button>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div id="emptyNotice" class="text-center py-4 text-muted">No process stages added yet. Select a stage above and click "Add Stage".</div>
                    @endforelse
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 1]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
                    @if(!$isReadOnly)
                        <button type="submit" class="btn btn-primary-sattva">Save & Proceed to Activities <i class="bi bi-arrow-right ms-1"></i></button>
                    @else
                        <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 3]) }}" class="btn btn-primary-sattva">Next Step <i class="bi bi-arrow-right ms-1"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('stagesContainer');
    if (el && !@json($isReadOnly)) {
        Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function () {
                updateSequenceBadges();
            }
        });
    }
});

function updateSequenceBadges() {
    const rows = document.querySelectorAll('#stagesContainer .stage-row');
    rows.forEach((row, i) => {
        const badge = row.querySelector('.sequence-badge');
        if (badge) badge.textContent = 'Seq ' + (i + 1);
    });
}

function addStageToFlow() {
    const picker = document.getElementById('stagePicker');
    const stageId = picker.value;
    if (!stageId) return;
    const stageName = picker.options[picker.selectedIndex].getAttribute('data-name');

    const emptyNotice = document.getElementById('emptyNotice');
    if (emptyNotice) emptyNotice.remove();

    const container = document.getElementById('stagesContainer');
    const rowCount = container.querySelectorAll('.stage-row').length;

    const div = document.createElement('div');
    div.className = 'list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 stage-row border rounded mb-2 bg-white';
    div.setAttribute('data-id', stageId);
    div.innerHTML = `
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-grip-vertical fs-4 text-muted cursor-grab drag-handle"></i>
            <span class="badge bg-secondary sequence-badge rounded-pill px-3 py-2">Seq ${rowCount + 1}</span>
            <div>
                <h6 class="mb-0 fw-bold stage-title">${stageName}</h6>
                <small class="text-muted">Added</small>
            </div>
            <input type="hidden" name="stages[]" value="${stageId}">
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="form-check form-switch mb-0">
                <input class="form-check-input" type="checkbox" name="qc_required[${rowCount}]" value="1">
                <label class="form-check-label small">Mandatory QC</label>
            </div>
            <input type="text" name="instructions[]" class="form-control form-control-sm" placeholder="Stage instructions..." style="width: 250px;">
            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeStage(this)"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
    picker.value = '';
    updateSequenceBadges();
}

function removeStage(btn) {
    btn.closest('.stage-row').remove();
    updateSequenceBadges();
}
</script>
@endsection