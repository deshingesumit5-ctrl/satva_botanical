@extends('layouts.app')
@section('title', 'Make Supplier Payment')
@section('content')
<div class="container-fluid px-0" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Make Supplier Payment</h2>
            <p class="page-subtitle">Disburse payment against vendor invoice.</p>
        </div>
        <a href="{{ route('sales.payments.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.payments.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label required">Supplier</label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Payment Amount (₹)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="NEFT/RTGS">NEFT / RTGS</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Bank Reference / UTR Number</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR12345678">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.payments.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection