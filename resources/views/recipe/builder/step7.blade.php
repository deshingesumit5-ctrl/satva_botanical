@extends('layouts.app')
@section('title', 'Recipe Builder - Step 7: QC Tests')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> | Step 7: Quality Control Standards & Hold Points</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 7]) }}" method="POST">
        @csrf
        @foreach($recipeStages as $rs)
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-success"><span class="badge bg-light text-dark border me-2">Seq {{ $rs->sequence_no }}</span> {{ $rs->stage->name ?? 'Stage' }}</h6>
                @if(!$isReadOnly)
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQcRow({{ $rs->id }})"><i class="bi bi-plus-lg me-1"></i> Add QC Test</button>
                @endif
            </div>
            <div class="card-sattva-body p-3">
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
                                            <option value="{{ $test->id }}" {{ $q->qc_test_id == $test->id ? 'selected' : '' }}>{{ $test->name }}</option>
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
                    <option value="{{ $test->id }}">{{ $test->name }}</option>
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