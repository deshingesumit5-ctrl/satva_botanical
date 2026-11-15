@extends('layouts.app')
@section('title', 'Purchase Bill Details')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Vendor Bill: {{ $purchaseInvoice->invoice_number }}</h2>
            <p class="page-subtitle">Supplier: <strong>{{ $purchaseInvoice->supplier->name ?? '-' }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sales.payments.create', ['invoice_id' => $purchaseInvoice->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-cash me-1"></i> Pay Vendor</a>
            <a href="{{ route('sales.purchase-invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><label class="text-muted small">Supplier</label><div class="fw-bold">{{ $purchaseInvoice->supplier->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">GRN Ref</label><div class="fw-bold">{{ $purchaseInvoice->grn->grn_number ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Invoice Date</label><div class="fw-bold">{{ $purchaseInvoice->invoice_date ? \Carbon\Carbon::parse($purchaseInvoice->invoice_date)->format('d-m-Y') : '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Total Amount</label><div class="fw-bold text-success fs-5">₹{{ number_format($purchaseInvoice->total_amount, 2) }}</div></div>
            </div>
        </div>
    </div>
</div>
@endsection