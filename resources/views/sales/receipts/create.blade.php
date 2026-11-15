@extends('layouts.app')
@section('title', 'Log Customer Receipt')
@section('content')
<div class="container-fluid px-0" style="max-width: 600px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Receive Customer Payment</h2>
            <p class="page-subtitle">Record customer settlement against sales invoice.</p>
        </div>
        <a href="{{ route('sales.receipts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.receipts.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label required">Customer</label>
                    <select name="customer_id" class="form-select" required>
                        <option value="">Select Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Amount Received (₹)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="Bank Transfer">Bank Transfer / NEFT</option>
                            <option value="Cheque">Cheque</option>
                            <option value="LC">Letter of Credit (LC)</option>
                        </select>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Reference / UTR / Cheque No</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR998877">
                </div>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.receipts.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection