@extends('layouts.app')
@section('title', 'Recipe Builder - Step 2: Stage Flow')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> ({{ $recipe->recipe_code }}) | Step 2: Process Stage Flow & Completion Requirements</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 fw-bold">Process Flow Sequence & Completion Requirements</h6>
                <small class="text-muted">Drag & drop items using the handle icon <i class="bi bi-grip-vertical"></i> to reorder. Configure stage-specific Completion Requirements (Section 7.2) per stage occurrence.</small>
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
                    @php
                        $acceptIds = is_array($rs->acceptance_parameter_ids) ? $rs->acceptance_parameter_ids : [];
                        $procConds = is_array($rs->expected_process_conditions) ? $rs->expected_process_conditions : [];
                        $mappedParams = $rs->parameters->count() > 0
                            ? $rs->parameters->map(fn($rp) => $rp->parameter)->filter()
                            : ($parameters ?? collect());
                    @endphp
                    <div class="list-group-item p-3 stage-row border rounded mb-3 bg-white" data-id="{{ $rs->stage_id }}" data-idx="{{ $idx }}">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-grip-vertical fs-4 text-muted cursor-grab drag-handle"></i>
                                <span class="badge bg-secondary sequence-badge rounded-pill px-3 py-2">Seq {{ $rs->sequence_no }}</span>
                                <div>
                                    <h6 class="mb-0 fw-bold stage-title">{{ $rs->stage->name ?? 'Stage' }}</h6>
                                    <small class="text-muted">Occurrence #{{ $rs->occurrence_no }}</small>
                                </div>
                                <input type="hidden" name="stages[{{ $idx }}]" value="{{ $rs->stage_id }}">
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <input type="text" name="instructions[{{ $idx }}]" class="form-control form-control-sm" placeholder="Stage instructions..." value="{{ $rs->instructions }}" style="width: 240px;" {{ $isReadOnly ? 'disabled' : '' }}>
                                @if(!$isReadOnly)
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeStage(this)"><i class="bi bi-trash"></i></button>
                                @endif
                            </div>
                        </div>

                        <!-- Completion Requirements Section (Section 7.2) -->
                        <div class="mt-3 p-3 rounded-3 bg-light border">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold text-uppercase small text-success"><i class="bi bi-list-check me-1"></i> Completion Requirements (Moving to Next Stage)</span>
                                <span class="badge bg-white text-secondary border small">Recipe Version Locked Once Approved</span>
                            </div>
                            <div class="row g-3 align-items-center">
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Minimum Repetitions</label>
                                    <input type="number" min="1" max="20" name="min_repetitions[{{ $idx }}]" value="{{ $rs->min_repetitions ?? 1 }}" class="form-control form-control-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" name="qc_required[{{ $idx }}]" value="1" id="qc_req_{{ $idx }}" {{ $rs->qc_required ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}>
                                        <label class="form-check-label small fw-semibold" for="qc_req_{{ $idx }}">QC Approval Required</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" name="supervisor_auth_required[{{ $idx }}]" value="1" id="sup_auth_{{ $idx }}" {{ $rs->supervisor_auth_required ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}>
                                        <label class="form-check-label small fw-semibold" for="sup_auth_{{ $idx }}">Supervisor Authorization Required</label>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" name="is_optional[{{ $idx }}]" value="1" id="opt_{{ $idx }}" {{ $rs->is_optional ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}>
                                        <label class="form-check-label small" for="opt_{{ $idx }}">Optional Stage</label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Parameter Acceptance Criteria</label>
                                    <select name="acceptance_parameter_ids[{{ $idx }}][]" class="form-select form-select-sm" multiple style="min-height: 60px;" {{ $isReadOnly ? 'disabled' : '' }}>
                                        @foreach($parameters ?? [] as $param)
                                            <option value="{{ $param->id }}" {{ in_array((int)$param->id, array_map('intval', $acceptIds), true) ? 'selected' : '' }}>
                                                {{ $param->name }} ({{ $param->uom }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Expected Process Conditions -->
                            <div class="mt-3 pt-2 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label small mb-0">Expected Process Conditions (Parameter + Operator + Target + Optional Hold Time)</label>
                                    @if(!$isReadOnly)
                                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" onclick="addProcessConditionRow({{ $idx }})">
                                            <i class="bi bi-plus-lg"></i> Add Condition
                                        </button>
                                    @endif
                                </div>
                                <div id="procCondContainer_{{ $idx }}">
                                    @foreach($procConds as $cIdx => $cond)
                                        <div class="row g-2 mb-2 align-items-center proc-cond-row">
                                            <div class="col-md-3">
                                                <select name="expected_process_conditions[{{ $idx }}][{{ $cIdx }}][parameter_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                                    <option value="">Select Parameter</option>
                                                    @foreach($parameters ?? [] as $param)
                                                        <option value="{{ $param->id }}" {{ ($cond['parameter_id'] ?? null) == $param->id ? 'selected' : '' }}>{{ $param->name }} ({{ $param->uom }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="expected_process_conditions[{{ $idx }}][{{ $cIdx }}][operator]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                                    @foreach(['=', '>', '>=', '<', '<=', 'between'] as $op)
                                                        <option value="{{ $op }}" {{ ($cond['operator'] ?? '=') === $op ? 'selected' : '' }}>{{ $op }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="expected_process_conditions[{{ $idx }}][{{ $cIdx }}][value]" value="{{ $cond['value'] ?? '' }}" class="form-control form-control-sm" placeholder="Value / Min" {{ $isReadOnly ? 'disabled' : '' }}>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" name="expected_process_conditions[{{ $idx }}][{{ $cIdx }}][max_value]" value="{{ $cond['max_value'] ?? '' }}" class="form-control form-control-sm" placeholder="Max (if between)" {{ $isReadOnly ? 'disabled' : '' }}>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" min="0" name="expected_process_conditions[{{ $idx }}][{{ $cIdx }}][hold_time_minutes]" value="{{ $cond['hold_time_minutes'] ?? '' }}" class="form-control form-control-sm" placeholder="Hold (mins)" {{ $isReadOnly ? 'disabled' : '' }}>
                                            </div>
                                            @if(!$isReadOnly)
                                                <div class="col-md-1 text-end">
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('.proc-cond-row').remove()"><i class="bi bi-x-lg"></i></button>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
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
const availableParametersHtml = `@foreach($parameters ?? [] as $param)<option value="{{ $param->id }}">{{ $param->name }} ({{ $param->uom }})</option>@endforeach`;

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

function addProcessConditionRow(stageIdx) {
    const container = document.getElementById('procCondContainer_' + stageIdx);
    if (!container) return;
    const cIdx = Date.now() + Math.floor(Math.random() * 1000);
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 align-items-center proc-cond-row';
    div.innerHTML = `
        <div class="col-md-3">
            <select name="expected_process_conditions[${stageIdx}][${cIdx}][parameter_id]" class="form-select form-select-sm">
                <option value="">Select Parameter</option>
                ${availableParametersHtml}
            </select>
        </div>
        <div class="col-md-2">
            <select name="expected_process_conditions[${stageIdx}][${cIdx}][operator]" class="form-select form-select-sm">
                <option value="=">=</option>
                <option value=">">&gt;</option>
                <option value=">=">&gt;=</option>
                <option value="<">&lt;</option>
                <option value="<=">&lt;=</option>
                <option value="between">between</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="expected_process_conditions[${stageIdx}][${cIdx}][value]" class="form-control form-control-sm" placeholder="Value / Min">
        </div>
        <div class="col-md-2">
            <input type="text" name="expected_process_conditions[${stageIdx}][${cIdx}][max_value]" class="form-control form-control-sm" placeholder="Max (if between)">
        </div>
        <div class="col-md-2">
            <input type="number" min="0" name="expected_process_conditions[${stageIdx}][${cIdx}][hold_time_minutes]" class="form-control form-control-sm" placeholder="Hold (mins)">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('.proc-cond-row').remove()"><i class="bi bi-x-lg"></i></button>
        </div>
    `;
    container.appendChild(div);
}

function addStageToFlow() {
    const picker = document.getElementById('stagePicker');
    const stageId = picker.value;
    if (!stageId) return;
    const stageName = picker.options[picker.selectedIndex].getAttribute('data-name');

    const emptyNotice = document.getElementById('emptyNotice');
    if (emptyNotice) emptyNotice.remove();

    const container = document.getElementById('stagesContainer');
    const rowIdx = Date.now();
    const rowCount = container.querySelectorAll('.stage-row').length;

    const div = document.createElement('div');
    div.className = 'list-group-item p-3 stage-row border rounded mb-3 bg-white';
    div.setAttribute('data-id', stageId);
    div.setAttribute('data-idx', rowIdx);
    div.innerHTML = `
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-grip-vertical fs-4 text-muted cursor-grab drag-handle"></i>
                <span class="badge bg-secondary sequence-badge rounded-pill px-3 py-2">Seq ${rowCount + 1}</span>
                <div>
                    <h6 class="mb-0 fw-bold stage-title">${stageName}</h6>
                    <small class="text-muted">Added</small>
                </div>
                <input type="hidden" name="stages[${rowIdx}]" value="${stageId}">
            </div>
            <div class="d-flex align-items-center gap-3">
                <input type="text" name="instructions[${rowIdx}]" class="form-control form-control-sm" placeholder="Stage instructions..." style="width: 240px;">
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeStage(this)"><i class="bi bi-trash"></i></button>
            </div>
        </div>
        <div class="mt-3 p-3 rounded-3 bg-light border">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-uppercase small text-success"><i class="bi bi-list-check me-1"></i> Completion Requirements (Moving to Next Stage)</span>
            </div>
            <div class="row g-3 align-items-center">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Minimum Repetitions</label>
                    <input type="number" min="1" max="20" name="min_repetitions[${rowIdx}]" value="1" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="qc_required[${rowIdx}]" value="1" id="qc_req_${rowIdx}">
                        <label class="form-check-label small fw-semibold" for="qc_req_${rowIdx}">QC Approval Required</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="supervisor_auth_required[${rowIdx}]" value="1" id="sup_auth_${rowIdx}">
                        <label class="form-check-label small fw-semibold" for="sup_auth_${rowIdx}">Supervisor Authorization Required</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_optional[${rowIdx}]" value="1" id="opt_${rowIdx}">
                        <label class="form-check-label small" for="opt_${rowIdx}">Optional Stage</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Parameter Acceptance Criteria</label>
                    <select name="acceptance_parameter_ids[${rowIdx}][]" class="form-select form-select-sm" multiple style="min-height: 60px;">
                        ${availableParametersHtml}
                    </select>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label small mb-0">Expected Process Conditions</label>
                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" onclick="addProcessConditionRow(${rowIdx})">
                        <i class="bi bi-plus-lg"></i> Add Condition
                    </button>
                </div>
                <div id="procCondContainer_${rowIdx}"></div>
            </div>
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
