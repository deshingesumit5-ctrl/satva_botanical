@extends('layouts.app')
@section('title', 'Edit Production Plan')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Plan: {{ $plan->plan_no }}</h2>
            <p class="page-subtitle">Update schedule and planned campaign volume.</p>
        </div>
        <a href="{{ route('production.plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('production.plans.update', $plan) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Finished Good Product</label>
                        <select name="product_id" class="form-select" required>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ $plan->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Approved Master Recipe</label>
                        <select name="recipe_id" class="form-select" required>
                            @foreach($recipes as $r)
                                <option value="{{ $r->id }}" {{ $plan->recipe_id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Plant Facility</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $pl)
                                <option value="{{ $pl->id }}" {{ $plan->plant_id == $pl->id ? 'selected' : '' }}>{{ $pl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Planned Total Output</label>
                        <input type="number" step="0.01" name="planned_quantity" class="form-control" value="{{ old('planned_quantity', $plan->planned_quantity) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">UOM</label>
                        <select name="uom_id" class="form-select" required>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}" {{ $plan->uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Scheduled Start Date</label>
                        <input type="date" name="start_date" class="form-control" required value="{{ old('start_date', $plan->start_date ? \Carbon\Carbon::parse($plan->start_date)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Scheduled End Date</label>
                        <input type="date" name="end_date" class="form-control" required value="{{ old('end_date', $plan->end_date ? \Carbon\Carbon::parse($plan->end_date)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Notes & Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $plan->remarks) }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('production.plans.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Production Plan?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection