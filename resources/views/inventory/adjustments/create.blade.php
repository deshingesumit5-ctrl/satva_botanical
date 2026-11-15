@extends('layouts.app')
@section('title', 'New Stock Adjustment')
@section('content')
<div class="container-fluid px-0" style="max-width: 700px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">New Stock Adjustment</h2>
            <p class="page-subtitle">Reconcile lot balance due to physical count discrepancy or process loss.</p>
        </div>
        <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('inventory.adjustments.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label required">Select Stock Lot</label>
                    <select name="stock_lot_id" class="form-select" required>
                        <option value="">Select Lot to Adjust</option>
                        @foreach($lots as $lot)
                            <option value="{{ $lot->id }}">
                                {{ $lot->lot_number }} | {{ $lot->material->name ?? $lot->product->name ?? '' }} (Current: {{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }} in {{ $lot->warehouse->name ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Adjustment Type</label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="Deduction">Deduction (Decrease Qty / Loss / Spoilage)</option>
                            <option value="Addition">Addition (Increase Qty / Found Stock)</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Quantity</label>
                        <input type="number" step="0.001" name="quantity" class="form-control" required placeholder="Quantity to adjust">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label required">Mandatory Reason / Audit Remarks</label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="State exact cause: e.g. Annual physical inventory count variance, solvent tank evaporation test..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('inventory.adjustments.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Post Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection