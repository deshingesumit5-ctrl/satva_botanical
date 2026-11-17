@extends('layouts.app')
@section('title', 'Recipe Builder - Step 7: QC & Completion Requirements')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> | Step 7: Quality Control & Stage Completion Requirements (Section 7.2)</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 7]) }}" method="POST">
        @csrf
        @foreach($recipeStages as $rs)
        @php
            $acceptIds = is_array($rs->acceptance_parameter_ids) ? array_map('intval', $rs->acceptance_parameter_ids) : [];
            $procConds = is_array($rs->expected_process_conditions) ? $rs->expected_process_conditions : [];
            $stageParams = $rs->parameters->count() > 0 ? $rs->parameters : collect();
        @endphp
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-success">
                    <span class="badge bg-light text-dark border me-2">Seq {{ $rs->sequence_no }}</span>
                    {{ $rs->stage->name ?? 'Stage' }}
                    <small class="text-muted ms-1">(Occurrence #{{ $rs->occurrence_no }})</small>
                </h6>
                @if(!$isReadOnly)
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQcRow({{ $rs->id }})"><i class="bi bi-plus-lg me-1"></i> Add QC Test</button>
                @endif
            </div>
            <div class="card-sattva-body p-3">
                <!-- Completion Requirements Section -->
                <div class="p-3 rounded-3 bg-light border mb-3">
                    <div class="fw-bold text-uppercase small text-success mb-2">
                        <i class="bi bi-shield-check me-1"></i> Completion Requirements (Moving to Next Stage)
                    </div>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-2">
                            <label class="form-label small mb-1">Minimum Repetitions</label>
                            <input type="number" min="1" max="20" name="completion_requirements[{{ $rs->id }}][min_repetitions]" value="{{ $rs->min_repetitions ?? 1 }}" class="form-control form-control-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="completion_requirements[{{ $rs->id }}][qc_required]" value="1" id="s7_qc_{{ $rs->id }}" {{ $rs->qc_required ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}>
                                <label class="form-check-label small fw-semibold" for="s7_qc_{{ $rs->id }}">QC Approval Required</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" name="completion_requirements[{{ $rs->id }}][supervisor_auth_required]" value="1" id="s7_sup_{{ $rs->id }}" {{ $rs->supervisor_auth_required ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}>
                                <label class="form-check-label small fw-semibold" for="s7_sup_{{ $rs->id }}">Supervisor Authorization Required</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Parameter Acceptance Criteria (within recipe min/max)</label>
                            <div class="d-flex flex-wrap gap-2">
                                @forelse($stageParams as $rp)
                                    @if($rp->parameter)
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox"
                                                name="completion_requirements[{{ $rs->id }}][acceptance_parameter_ids][]"
                                                value="{{ $rp->parameter_id }}"
                                                id="acc_p_{{ $rs->id }}_{{ $rp->parameter_id }}"
                                                {{ in_array((int)$rp->parameter_id, $acceptIds, true) ? 'checked' : '' }}
                                                {{ $isReadOnly ? 'disabled' : '' }}>
                                            <label class="form-check-label small" for="acc_p_{{ $rs->id }}_{{ $rp->parameter_id }}">
                                                {{ $rp->parameter->name }} ({{ $rp->min_value ?? '-' }}–{{ $rp->max_value ?? '-' }})
                                            </label>
                                        </div>
                                    @endif
                                @empty
                                    <span class="text-muted small">No stage parameters mapped in Step 4.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Expected Process Conditions -->
                    <div class="mt-3 pt-2 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small mb-0">Expected Process Conditions</label>
                            @if(!$isReadOnly)
                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" onclick="addS7CondRow({{ $rs->id }})">
                                    <i class="bi bi-plus-lg"></i> Add Condition
                                </button>
                            @endif
                        </div>
                        <div id="s7CondRows_{{ $rs->id }}">
                            @foreach($procConds as $cIdx => $cond)
                                <div class="row g-2 mb-2 align-items-center s7-cond-row">
                                    <div class="col-md-3">
                                        <select name="completion_requirements[{{ $rs->id }}][expected_process_conditions][{{ $cIdx }}][parameter_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                            <option value="">Select Parameter</option>
                                            @foreach($parameters ?? [] as $param)
                                                <option value="{{ $param->id }}" {{ ($cond['parameter_id'] ?? null) == $param->id ? 'selected' : '' }}>{{ $param->name }} ({{ $param->uom }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="completion_requirements[{{ $rs->id }}][expected_process_conditions][{{ $cIdx }}][operator]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                            @foreach(['=', '>', '>=', '<', '<=', 'between'] as $op)
                                                <option value="{{ $op }}" {{ ($cond['operator'] ?? '=') === $op ? 'selected' : '' }}>{{ $op }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="completion_requirements[{{ $rs->id }}][expected_process_conditions][{{ $cIdx }}][value]" value="{{ $cond['value'] ?? '' }}" class="form-control form-control-sm" placeholder="Value / Min" {{ $isReadOnly ? 'disabled' : '' }}>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="completion_requirements[{{ $rs->id }}][expected_process_conditions][{{ $cIdx }}][max_value]" value="{{ $cond['max_value'] ?? '' }}" class="form-control form-control-sm" placeholder="Max (if between)" {{ $isReadOnly ? 'disabled' : '' }}>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" min="0" name="completion_requirements[{{ $rs->id }}][expected_process_conditions][{{ $cIdx }}][hold_time_minutes]" value="{{ $cond['hold_time_minutes'] ?? '' }}" class="form-control form-control-sm" placeholder="Hold (mins)" {{ $isReadOnly ? 'disabled' : '' }}>
                                    </div>
                                    @if(!$isReadOnly)
                                        <div class="col-md-1 text-end">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('.s7-cond-row').remove()"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- QC Tests Table -->
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="35%">QC Test</th>
                                <th width="15%">Min Spec</th>
                                <th width="15%">Max Spec</th>
                                <th width="15%">Target</th>
                                <th width="10%">Blocks Next Stage</th>
                                @if(!$isReadOnly)<th width="10%" class="text-end">Action</th>@endif
                            </tr>
                        </thead>
                        <tbody id="qcRows_{{ $rs->id }}">
                            @forelse($rs->qcTests as $idx => $q)
                            <tr>
                                <td>
                                    <select name="qc_tests[{{ $rs->id }}][{{ $idx }}][qc_test_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                        @foreach($qcTests as $test)
                                            <option value="{{ $test->id }}" {{ $q->qc_test_id == $test->id ? 'selected' : '' }}>{{ $test->name ?? $test->test_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" name="qc_tests[{{ $rs->id }}][{{ $idx }}][min_value]" class="form-control form-control-sm" value="{{ $q->min_value }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td><input type="number" step="0.001" name="qc_tests[{{ $rs->id }}][{{ $idx }}][max_value]" class="form-control form-control-sm" value="{{ $q->max_value }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td><input type="text" name="qc_tests[{{ $rs->id }}][{{ $idx }}][target_value]" class="form-control form-control-sm" value="{{ $q->target_value }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="qc_tests[{{ $rs->id }}][{{ $idx }}][blocks_next_stage]" value="1" {{ $q->blocks_next_stage ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}></div></td>
                                @if(!$isReadOnly)
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                @endif
                            </tr>
                            @empty
                            <tr class="no-data"><td colspan="6" class="text-muted text-center py-2">No mandatory QC testing required for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 6]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @if(!$isReadOnly)
                <button type="submit" class="btn btn-primary-sattva">Save & Proceed to Review <i class="bi bi-arrow-right ms-1"></i></button>
            @else
                <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 8]) }}" class="btn btn-primary-sattva">Next Step <i class="bi bi-arrow-right ms-1"></i></a>
            @endif
        </div>
    </form>
</div>

<script>
const s7ParamsHtml = `@foreach($parameters ?? [] as $param)<option value="{{ $param->id }}">{{ $param->name }} ({{ $param->uom }})</option>@endforeach`;

function addS7CondRow(rsId) {
    const container = document.getElementById('s7CondRows_' + rsId);
    if (!container) return;
    const cIdx = Date.now() + Math.floor(Math.random() * 1000);
    const div = document.createElement('div');
    div.className = 'row g-2 mb-2 align-items-center s7-cond-row';
    div.innerHTML = `
        <div class="col-md-3">
            <select name="completion_requirements[${rsId}][expected_process_conditions][${cIdx}][parameter_id]" class="form-select form-select-sm">
                <option value="">Select Parameter</option>
                ${s7ParamsHtml}
            </select>
        </div>
        <div class="col-md-2">
            <select name="completion_requirements[${rsId}][expected_process_conditions][${cIdx}][operator]" class="form-select form-select-sm">
                <option value="=">=</option>
                <option value=">">&gt;</option>
                <option value=">=">&gt;=</option>
                <option value="<">&lt;</option>
                <option value="<=">&lt;=</option>
                <option value="between">between</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" name="completion_requirements[${rsId}][expected_process_conditions][${cIdx}][value]" class="form-control form-control-sm" placeholder="Value / Min">
        </div>
        <div class="col-md-2">
            <input type="text" name="completion_requirements[${rsId}][expected_process_conditions][${cIdx}][max_value]" class="form-control form-control-sm" placeholder="Max (if between)">
        </div>
        <div class="col-md-2">
            <input type="number" min="0" name="completion_requirements[${rsId}][expected_process_conditions][${cIdx}][hold_time_minutes]" class="form-control form-control-sm" placeholder="Hold (mins)">
        </div>
        <div class="col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('.s7-cond-row').remove()"><i class="bi bi-x-lg"></i></button>
        </div>
    `;
    container.appendChild(div);
}

function addQcRow(rsId) {
    const tbody = document.getElementById('qcRows_' + rsId);
    const noData = tbody.querySelector('.no-data');
    if (noData) noData.remove();
    const idx = Date.now();
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="qc_tests[${rsId}][${idx}][qc_test_id]" class="form-select form-select-sm">
                @foreach($qcTests as $test)
                    <option value="{{ $test->id }}">{{ $test->name ?? $test->test_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" name="qc_tests[${rsId}][${idx}][min_value]" class="form-control form-control-sm" placeholder="Min"></td>
        <td><input type="number" step="0.001" name="qc_tests[${rsId}][${idx}][max_value]" class="form-control form-control-sm" placeholder="Max"></td>
        <td><input type="text" name="qc_tests[${rsId}][${idx}][target_value]" class="form-control form-control-sm" placeholder="Target"></td>
        <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="qc_tests[${rsId}][${idx}][blocks_next_stage]" value="1" checked></div></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection
