@extends('layouts.app')
@section('title', 'Purchase Bill Details')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">Vendor Bill: {{ $purchaseInvoice->internal_invoice_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $purchaseInvoice->payment_status)) }}">{{ $purchaseInvoice->payment_status }}</span>
            </div>
            <p class="page-subtitle">
                Supplier: <strong>{{ $purchaseInvoice->supplier->name ?? '-' }}</strong>
                | Vendor Invoice: <strong>{{ $purchaseInvoice->supplier_invoice_number }}</strong>
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($purchaseInvoice->payment_status !== 'Paid')
                <a href="{{ route('sales.payments.create', ['invoice_id' => $purchaseInvoice->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-cash me-1"></i> Pay Vendor</a>
            @endif
            <a href="{{ route('sales.purchase-invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><label class="text-muted small">Supplier</label><div class="fw-bold">{{ $purchaseInvoice->supplier->name ?? '-' }}</div></div>
                <div class="col-md-2"><label class="text-muted small">GRN Ref</label><div class="fw-bold">{{ $purchaseInvoice->grn->grn_number ?? '-' }}</div></div>
                <div class="col-md-2"><label class="text-muted small">Invoice Date</label><div class="fw-bold">{{ $purchaseInvoice->invoice_date ? \Carbon\Carbon::parse($purchaseInvoice->invoice_date)->format('d-m-Y') : '-' }}</div></div>
                <div class="col-md-2"><label class="text-muted small">Paid Amount</label><div class="fw-bold text-primary">₹{{ number_format($purchaseInvoice->paid_amount, 2) }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Total / Balance</label><div class="fw-bold text-success fs-5">₹{{ number_format($purchaseInvoice->total_amount, 2) }} <span class="text-danger fs-6">(Due: ₹{{ number_format($purchaseInvoice->balance_amount, 2) }})</span></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
