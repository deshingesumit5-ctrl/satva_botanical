@extends('layouts.app')
@section('title', 'Customer: ' . $customer->name)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $customer->name }}</h2>
                <span class="badge-status badge-status-{{ strtolower($customer->status) }}">{{ $customer->status }}</span>
            </div>
            <p class="page-subtitle">Customer Code: {{ $customer->customer_code }} | GSTIN: {{ $customer->gstin ?? 'N/A' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('masters.customers.edit', $customer) }}" class="btn btn-primary-sattva"><i class="bi bi-pencil me-1"></i> Edit</a>
            <a href="{{ route('masters.customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Commercial & Terms</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="row g-3">
                        <div class="col-6"><label class="text-muted small">Contact Person</label><div class="fw-semibold">{{ $customer->contact_person ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">Mobile</label><div class="fw-semibold">{{ $customer->mobile ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">Email</label><div class="fw-semibold">{{ $customer->email ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">Payment Terms</label><div class="fw-semibold">{{ $customer->payment_terms_days ?? 30 }} Days</div></div>
                        <div class="col-6"><label class="text-muted small">Credit Limit</label><div class="fw-semibold text-success">₹{{ number_format($customer->credit_limit ?? 0, 2) }}</div></div>
                        <div class="col-6"><label class="text-muted small">PAN</label><div class="fw-semibold">{{ $customer->pan ?? '-' }}</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Addresses</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small">Billing Address</label>
                        <p class="mb-0">{{ $customer->billing_address ?? 'No billing address provided.' }}</p>
                    </div>
                    <div>
                        <label class="text-muted small">Shipping Address</label>
                        <p class="mb-0">{{ $customer->shipping_address ?? 'Same as billing address.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection