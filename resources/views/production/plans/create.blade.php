@extends('layouts.app')
@section('title', 'Create Production Plan')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">New Production Plan</h2>
            <p class="page-subtitle">Schedule a campaign for finished botanical extract.</p>
        </div>
        <a href="{{ route('production.plans.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('production.plans.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Finished Good Product</label>
                        <select name="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->product_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Approved Master Recipe</label>
                        <select name="recipe_id" class="form-select" required>
                            <option value="">Select Recipe</option>
                            @foreach($recipes as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->recipe_code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Plant Facility</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $pl)
                                <option value="{{ $pl->id }}">{{ $pl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Planned Total Output</label>
                        <input type="number" step="0.01" name="planned_quantity" class="form-control" required placeholder="e.g. 5000">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">UOM</label>
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
                        <input type="date" name="end_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Notes & Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Campaign notes, export order linkage..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('production.plans.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Create Plan & Calculate Requirements</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection