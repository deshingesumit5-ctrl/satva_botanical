@extends('layouts.app')
@section('title', 'Supplier: ' . $supplier->name)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $supplier->name }}</h2>
                <span class="badge-status badge-status-{{ strtolower($supplier->status) }}">{{ $supplier->status }}</span>
            </div>
            <p class="page-subtitle">Supplier Code: {{ $supplier->supplier_code }} | GSTIN: {{ $supplier->gstin ?? 'N/A' }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('masters.suppliers.edit', $supplier) }}" class="btn btn-primary-sattva"><i class="bi bi-pencil me-1"></i> Edit</a>
            <a href="{{ route('masters.suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Contact & Commercial</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="row g-3">
                        <div class="col-6"><label class="text-muted small">Contact Person</label><div class="fw-semibold">{{ $supplier->contact_person ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">Mobile</label><div class="fw-semibold">{{ $supplier->mobile ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">Email</label><div class="fw-semibold">{{ $supplier->email ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">Payment Terms</label><div class="fw-semibold">{{ $supplier->payment_terms_days ?? 30 }} Days</div></div>
                        <div class="col-6"><label class="text-muted small">PAN</label><div class="fw-semibold">{{ $supplier->pan ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">GSTIN</label><div class="fw-semibold">{{ $supplier->gstin ?? '-' }}</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Registered Address</h6></div>
                <div class="card-sattva-body p-4">
                    <p class="mb-1">{{ $supplier->address_line ?? 'No address entered.' }}</p>
                    <p class="mb-0 text-muted">{{ $supplier->city }} {{ $supplier->state ? ', '.$supplier->state : '' }} {{ $supplier->pincode ? ' - '.$supplier->pincode : '' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection