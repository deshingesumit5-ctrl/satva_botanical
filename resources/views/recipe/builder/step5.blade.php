@extends('layouts.app')
@section('title', 'Recipe Builder - Step 5: Materials & Solvents')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> | Step 5: Raw Materials, Solvents & Outputs</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 5]) }}" method="POST">
        @csrf
        @foreach($recipeStages as $rs)
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-success"><span class="badge bg-light text-dark border me-2">Seq {{ $rs->sequence_no }}</span> {{ $rs->stage->name ?? 'Stage' }}</h6>
                @if(!$isReadOnly)
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addMaterialRow({{ $rs->id }})"><i class="bi bi-plus-lg me-1"></i> Add Material / Solvent</button>
                @endif
            </div>
            <div class="card-sattva-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="15%">Direction</th>
                                <th width="30%">Material / Item</th>
                                <th width="15%">Standard Qty</th>
                                <th width="15%">UOM</th>
                                <th width="15%">Category</th>
                                @if(!$isReadOnly)<th width="10%" class="text-end">Action</th>@endif
                            </tr>
                        </thead>
                        <tbody id="matRows_{{ $rs->id }}">
                            @forelse($rs->materials as $idx => $m)
                            <tr>
                                <td>
                                    <select name="materials[{{ $rs->id }}][{{ $idx }}][direction]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                        <option value="input" {{ $m->direction === 'input' ? 'selected' : '' }}>Input (Charged)</option>
                                        <option value="output" {{ $m->direction === 'output' ? 'selected' : '' }}>Output (Yield / Recovered)</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="materials[{{ $rs->id }}][{{ $idx }}][material_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                        <option value="">None / Intermediate</option>
                                        @foreach($materials as $mat)
                                            <option value="{{ $mat->id }}" {{ $m->material_id == $mat->id ? 'selected' : '' }}>{{ $mat->name }} ({{ $mat->material_type }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" name="materials[{{ $rs->id }}][{{ $idx }}][standard_qty]" class="form-control form-control-sm" value="{{ $m->standard_qty }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td>
                                    <select name="materials[{{ $rs->id }}][{{ $idx }}][uom_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                        @foreach($uoms as $u)
                                            <option value="{{ $u->id }}" {{ $m->uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="materials[{{ $rs->id }}][{{ $idx }}][material_category]" class="form-control form-control-sm" value="{{ $m->material_category }}" placeholder="Solvent, Raw..." {{ $isReadOnly ? 'disabled' : '' }}>
                                </td>
                                @if(!$isReadOnly)
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                @endif
                            </tr>
                            @empty
                            <tr class="no-data"><td colspan="6" class="text-muted text-center py-2">No material inputs or outputs defined for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 4]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @if(!$isReadOnly)
                <button type="submit" class="btn btn-primary-sattva">Save & Proceed to Equipment <i class="bi bi-arrow-right ms-1"></i></button>
            @else
                <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 6]) }}" class="btn btn-primary-sattva">Next Step <i class="bi bi-arrow-right ms-1"></i></a>
            @endif
        </div>
    </form>
</div>

<script>
function addMaterialRow(rsId) {
    const tbody = document.getElementById('matRows_' + rsId);
    const noData = tbody.querySelector('.no-data');
    if (noData) noData.remove();
    const idx = Date.now();
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="materials[${rsId}][${idx}][direction]" class="form-select form-select-sm">
                <option value="input">Input (Charged)</option>
                <option value="output">Output (Yield / Recovered)</option>
            </select>
        </td>
        <td>
            <select name="materials[${rsId}][${idx}][material_id]" class="form-select form-select-sm">
                <option value="">None / Intermediate</option>
                @foreach($materials as $mat)
                    <option value="{{ $mat->id }}">{{ $mat->name }} ({{ $mat->material_type }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" name="materials[${rsId}][${idx}][standard_qty]" class="form-control form-control-sm" value="100.000"></td>
        <td>
            <select name="materials[${rsId}][${idx}][uom_id]" class="form-select form-select-sm">
                @foreach($uoms as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="materials[${rsId}][${idx}][material_category]" class="form-control form-control-sm" placeholder="Solvent, Raw..."></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection