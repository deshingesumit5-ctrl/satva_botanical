@extends('layouts.app')
@section('title', 'Issue Credit/Debit Note')
@section('content')
<div class="container-fluid px-0" style="max-width: 650px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Issue Credit / Debit Note</h2>
            <p class="page-subtitle">Adjustment note against customer or supplier.</p>
        </div>
        <a href="{{ route('sales.notes.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.notes.store') }}" method="POST">
                @csrf
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Note Type</label>
                        <select name="note_type" class="form-select" required>
                            <option value="Credit">Credit Note</option>
                            <option value="Debit">Debit Note</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Adjusted Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Customer (if customer note)</label>
                    <select name="customer_id" class="form-select">
                        <option value="">None</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label required">Reason / Justification</label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Reason for rate adjustment, return, or quality discount..."></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.notes.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection