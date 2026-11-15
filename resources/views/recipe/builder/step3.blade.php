@extends('layouts.app')
@section('title', 'Recipe Builder - Step 3: Activities')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="page-title">Recipe Specification Builder</h2>
            <p class="page-subtitle">Recipe: <strong>{{ $recipe->name }}</strong> | Step 3: Stage Operational Activities</p>
        </div>
        <a href="{{ route('recipe.recipes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Exit Builder</a>
    </div>

    {!! \App\Services\RecipeBuilderNav::render($step, $recipe->id) !!}

    <form action="{{ route('recipe.builder.save-step', ['recipe' => $recipe->id, 'step' => 3]) }}" method="POST">
        @csrf
        @foreach($recipeStages as $rs)
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-success"><span class="badge bg-light text-dark border me-2">Seq {{ $rs->sequence_no }}</span> {{ $rs->stage->name ?? 'Stage' }} (Occurrence #{{ $rs->occurrence_no }})</h6>
                @if(!$isReadOnly)
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addActivityRow({{ $rs->id }})"><i class="bi bi-plus-lg me-1"></i> Add Activity</button>
                @endif
            </div>
            <div class="card-sattva-body p-3">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Activity</th>
                                <th width="20%">Std Duration (Mins)</th>
                                <th width="30%">Assigned Equipment</th>
                                <th width="10%">Mandatory</th>
                                @if(!$isReadOnly)<th width="10%" class="text-end">Action</th>@endif
                            </tr>
                        </thead>
                        <tbody id="actRows_{{ $rs->id }}">
                            @forelse($rs->activities as $idx => $a)
                            <tr>
                                <td>
                                    <select name="activities[{{ $rs->id }}][{{ $idx }}][activity_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                        @foreach($activities as $act)
                                            <option value="{{ $act->id }}" {{ $a->activity_id == $act->id ? 'selected' : '' }}>{{ $act->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="activities[{{ $rs->id }}][{{ $idx }}][duration]" class="form-control form-control-sm" value="{{ $a->standard_duration_minutes }}" {{ $isReadOnly ? 'disabled' : '' }}></td>
                                <td>
                                    <select name="activities[{{ $rs->id }}][{{ $idx }}][equipment_id]" class="form-select form-select-sm" {{ $isReadOnly ? 'disabled' : '' }}>
                                        <option value="">None</option>
                                        @foreach($equipments as $eq)
                                            <option value="{{ $eq->id }}" {{ $a->equipment_id == $eq->id ? 'selected' : '' }}>{{ $eq->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activities[{{ $rs->id }}][{{ $idx }}][is_mandatory]" value="1" {{ $a->is_mandatory ? 'checked' : '' }} {{ $isReadOnly ? 'disabled' : '' }}></div>
                                </td>
                                @if(!$isReadOnly)
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                @endif
                            </tr>
                            @empty
                            <tr class="no-data"><td colspan="5" class="text-muted text-center py-2">No activities defined for this stage.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach

        <div class="d-flex justify-content-between mb-4">
            <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 2]) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
            @if(!$isReadOnly)
                <button type="submit" class="btn btn-primary-sattva">Save & Proceed to Parameters <i class="bi bi-arrow-right ms-1"></i></button>
            @else
                <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 4]) }}" class="btn btn-primary-sattva">Next Step <i class="bi bi-arrow-right ms-1"></i></a>
            @endif
        </div>
    </form>
</div>

<script>
function addActivityRow(rsId) {
    const tbody = document.getElementById('actRows_' + rsId);
    const noData = tbody.querySelector('.no-data');
    if (noData) noData.remove();
    const idx = Date.now();
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="activities[${rsId}][${idx}][activity_id]" class="form-select form-select-sm">
                @foreach($activities as $act)
                    <option value="{{ $act->id }}">{{ $act->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="activities[${rsId}][${idx}][duration]" class="form-control form-control-sm" value="60"></td>
        <td>
            <select name="activities[${rsId}][${idx}][equipment_id]" class="form-select form-select-sm">
                <option value="">None</option>
                @foreach($equipments as $eq)
                    <option value="{{ $eq->id }}">{{ $eq->name }}</option>
                @endforeach
            </select>
        </td>
        <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activities[${rsId}][${idx}][is_mandatory]" value="1" checked></div></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
}
</script>
@endsection