@extends('layouts.app')
@section('title', 'Issue Production Batch')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Issue Production Batch (BMR Run)</h2>
            <p class="page-subtitle">Initialize a new manufacturing run against an approved recipe.</p>
        </div>
        <a href="{{ route('production.batches.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('production.batches.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Linked Production Plan</label>
                        <select name="production_plan_id" class="form-select">
                            <option value="">Independent Batch (No Plan)</option>
                            @foreach($plans as $pl)
                                <option value="{{ $pl->id }}" {{ $selectedPlan && $selectedPlan->id == $pl->id ? 'selected' : '' }}>
                                    {{ $pl->plan_no }} | {{ $pl->product->name ?? '' }} (Planned: {{ $pl->planned_quantity }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Finished Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Target Product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ $selectedPlan && $selectedPlan->product_id == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->product_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Approved Master Recipe</label>
                        <select name="recipe_id" class="form-select" required>
                            <option value="">Select Recipe</option>
                            @foreach($recipes as $r)
                                <option value="{{ $r->id }}" {{ $selectedPlan && $selectedPlan->recipe_id == $r->id ? 'selected' : '' }}>
                                    {{ $r->name }} ({{ $r->recipe_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Planned Batch Size</label>
                        <input type="number" step="0.01" name="planned_quantity" class="form-control" required placeholder="e.g. 1000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Batch UOM</label>
                        <select name="uom_id" class="form-select" required>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Scheduled Start Date</label>
                        <input type="date" name="start_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Scheduled End Date</label>
                        <input type="date" name="end_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+3 days')) }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Batch Specific Remarks / Instructions</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Operator instructions, batch campaign shift..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('production.batches.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Generate Batch & Allocate Materials</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection