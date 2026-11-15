@extends('layouts.app')
@section('title', 'Recipe Builder - Step 4: Parameters')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> | Step 4: Monitored Process Parameters</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 4]) }}" method="POST">
        @csrf
        @foreach($recipeStages as $rs)
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-success"><span class="badge bg-light text-dark border me-2">Seq {{ $rs->sequence_no }}</span> {{ $rs->stage->name ?? 'Stage' }}</h6>
                @if(!$isReadOnly)
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addParamRow({{ $rs->id }})"><i class="bi bi-plus-lg me-1"></i> Add Parameter</button>
                @endif
            </div>
            <div class="card-sattva-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Parameter</th>
                                <th width="15%">Target Value</th>
                                <th width="15%">Min Limit</th>
                                <th width="15%">Max Limit</th>
                                <th width="15%">Mandatory</th>
                                @if(!$isReadOnly)<th width="10%" class="text-end">Action</th>@endif
                            </tr>
                        </thead>
                        <tbody id="paramRows_{{ $rs->id }}">
                            @forelse($rs->parameters as $idx => $p)
                            <tr>
                                <td>
                                    <select name="parameters[{{ $rs->id }}][{{ $idx }}][parameter_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                        @foreach($parameters as $param)
                                            <option value="{{ $param->id }}" {{ $p->parameter_id == $param->id ? 'selected' : '' }}>{{ $param->name }} ({{ $param->parameter_type }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="parameters[{{ $rs->id }}][{{ $idx }}][target_value]" class="form-control form-control-sm" value="{{ $p->target_value }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td><input type="number" step="0.01" name="parameters[{{ $rs->id }}][{{ $idx }}][min_value]" class="form-control form-control-sm" value="{{ $p->min_value }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td><input type="number" step="0.01" name="parameters[{{ $rs->id }}][{{ $idx }}][max_value]" class="form-control form-control-sm" value="{{ $p->max_value }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="parameters[{{ $rs->id }}][{{ $idx }}][is_mandatory]" value="1" {{ $p->is_mandatory ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}></div></td>
                                @if(!$isReadOnly)
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                @endif
                            </tr>
                            @empty
                            <tr class="no-data"><td colspan="6" class="text-muted text-center py-2">No monitored parameters configured for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 3]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @if(!$isReadOnly)
                <button type="submit" class="btn btn-primary-sattva">Save & Proceed to Materials <i class="bi bi-arrow-right ms-1"></i></button>
            @else
                <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 5]) }}" class="btn btn-primary-sattva">Next Step <i class="bi bi-arrow-right ms-1"></i></a>
            @endif
        </div>
    </form>
</div>

<script>
function addParamRow(rsId) {
    const tbody = document.getElementById('paramRows_' + rsId);
    const noData = tbody.querySelector('.no-data');
    if (noData) noData.remove();
    const idx = Date.now();
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="parameters[${rsId}][${idx}][parameter_id]" class="form-select form-select-sm">
                @foreach($parameters as $param)
                    <option value="{{ $param->id }}">{{ $param->name }} ({{ $param->parameter_type }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="parameters[${rsId}][${idx}][target_value]" class="form-control form-control-sm" placeholder="Target"></td>
        <td><input type="number" step="0.01" name="parameters[${rsId}][${idx}][min_value]" class="form-control form-control-sm" placeholder="Min"></td>
        <td><input type="number" step="0.01" name="parameters[${rsId}][${idx}][max_value]" class="form-control form-control-sm" placeholder="Max"></td>
        <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="parameters[${rsId}][${idx}][is_mandatory]" value="1" checked></div></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection