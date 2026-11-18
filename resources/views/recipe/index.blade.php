@extends('layouts.app')
@section('title', 'Master Recipes')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Master Production Recipes (BMR Templates)</h2>
                <p class="page-subtitle">Configure multi-stage herbal extraction recipes, solvent recoveries, and QA test
                    parameters.</p>
            </div>
            <a href="{{ route('recipe.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-plus-lg me-1"></i> Add Recipe
            </a>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('recipe.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search recipe name, code, product..."></div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                            <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                        <a href="{{ route('recipe.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-sattva">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Recipe Code</th>
                            <th>Recipe Name</th>
                            <th>Finished Good Product</th>
                            <th>Active Version</th>
                            <th>Batch Size</th>
                            <th>Status</th>
                            <th width="160" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recipes as $idx => $r)
                            <tr>
                                <td>{{ $recipes->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $r->recipe_code }}</td>
                                <td class="fw-semibold text-dark">{{ $r->name }}</td>
                                <td>{{ $r->product->name ?? '-' }}</td>
                                <td>
                                    @if($r->currentVersion)
                                        <span
                                            class="badge bg-light text-dark border font-monospace">v{{ $r->currentVersion->version_number }}
                                            ({{ $r->currentVersion->status }})</span>
                                    @else
                                        <span class="text-muted small">No version</span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->currentVersion)
                                        {{ $r->currentVersion->reference_batch_size }}
                                        {{ $r->currentVersion->batchUom->symbol ?? '' }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('recipe.toggle-status', $r) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="button" class="border-0 bg-transparent p-0"
                                            data-confirm-name="{{ $r->name }}"
                                            onclick="confirmAction('Toggle status for ' + this.dataset.confirmName + '?', this.closest('form'))">
                                            <span
                                                class="badge-status badge-status-{{ strtolower($r->status) }}">{{ $r->status }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('recipe.builder.step', ['recipe' => $r->id, 'step' => 1]) }}"
                                        class="btn btn-sm btn-icon btn-outline-primary me-1" title="Recipe Builder"><i
                                            class="bi bi-diagram-3"></i></a>
                                    <a href="{{ route('recipe.show', $r) }}"
                                        class="btn btn-sm btn-icon btn-outline-secondary me-1" title="View Summary"><i
                                            class="bi bi-eye"></i></a>
                                    <a href="{{ route('recipe.versions', $r) }}"
                                        class="btn btn-sm btn-icon btn-outline-info me-1" title="Version History"><i
                                            class="bi bi-clock-history"></i></a>
                                    <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                        data-url="{{ route('recipe.destroy', $r) }}"
                                        data-name="{{ $r->recipe_name ?? $r->name }}" title="Delete"><i
                                            class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No recipes found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recipes->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $recipes->firstItem() }} to {{ $recipes->lastItem() }} of
                        {{ $recipes->total() }}</span>
                    {{ $recipes->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection