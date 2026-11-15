@extends('layouts.app')
@section('title', 'Recipe Versions: ' . $recipe->name)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Version History: {{ $recipe->name }}</h2>
            <p class="page-subtitle">Immutable audit trail of approved, active, and superseded recipe revisions.</p>
        </div>
        <div class="d-flex gap-2">
            <form action="{{ route('recipe.clone-version', $recipe) }}" method="POST" class="d-inline">
                @csrf
                <button type="button" class="btn btn-primary-sattva" onclick="confirmAction('Create a new Draft version cloned from the current version?', this.closest('form'))">
                    <i class="bi bi-copy me-1"></i> Create New Version Revision
                </button>
            </form>
            <a href="{{ route('recipe.show', $recipe) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Recipe</a>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Version #</th>
                        <th>Status</th>
                        <th>Batch Size</th>
                        <th>Effective From</th>
                        <th>Effective To</th>
                        <th>Approved By</th>
                        <th>Approved At</th>
                        <th>Remarks</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recipe->versions()->latest('version_number')->get() as $v)
                    <tr>
                        <td class="fw-bold font-monospace text-dark">v{{ $v->version_number }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($v->status) }}">{{ $v->status }}</span></td>
                        <td>{{ $v->reference_batch_size }} {{ $v->batchUom->symbol ?? '' }}</td>
                        <td>{{ $v->effective_from ? \Carbon\Carbon::parse($v->effective_from)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $v->effective_to ? \Carbon\Carbon::parse($v->effective_to)->format('d-m-Y') : 'Open' }}</td>
                        <td>{{ $v->approver->name ?? '-' }}</td>
                        <td>{{ $v->approved_at ? \Carbon\Carbon::parse($v->approved_at)->format('d-m-Y H:i') : '-' }}</td>
                        <td class="text-muted small">{{ $v->approval_remarks ?? '-' }}</td>
                        <td class="text-end">
                            @if(in_array($v->status, ['Draft', 'Pending Approval']))
                                <a href="{{ route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 1]) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i> Edit Builder</a>
                            @else
                                <span class="badge bg-light text-muted border"><i class="bi bi-lock me-1"></i>Locked</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection