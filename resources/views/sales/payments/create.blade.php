@extends('layouts.app')
@section('title', 'Make Supplier Payment')
@section('content')
<div class="container-fluid px-0" style="max-width: 650px;">
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
                @php
                    $preSelectedInv = $selectedInvoiceId ? $invoices->firstWhere('id', (int)$selectedInvoiceId) : null;
                @endphp
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Purchase Invoice</label>
                        <select name="purchase_invoice_id" class="form-select" onchange="syncSupplierFromInvoice(this)" required>
                            <option value="">Select Purchase Invoice</option>
                            @foreach($invoices as $inv)
                                <option value="{{ $inv->id }}"
                                    data-supplier="{{ $inv->supplier_id }}"
                                    data-balance="{{ number_format((float)$inv->balance_amount, 2, '.', '') }}"
                                    {{ (int)($selectedInvoiceId ?? 0) === $inv->id ? 'selected' : '' }}>
                                    {{ $inv->internal_invoice_number }} | {{ $inv->supplier->name ?? '' }} (Bal: ₹{{ number_format($inv->balance_amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @if($invoices->isEmpty())
                            <small class="text-warning">No unpaid purchase invoices found. Log a vendor bill first.</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ $preSelectedInv && $preSelectedInv->supplier_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" required value="{{ old('payment_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Payment Amount (₹)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ $preSelectedInv ? number_format((float)$preSelectedInv->balance_amount, 2, '.', '') : '' }}" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="Bank Transfer">Bank Transfer (NEFT / RTGS)</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Bank Reference / UTR Number</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR12345678">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional payment notes">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.payments.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.syncSupplierFromInvoice = function (selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const form = selectEl.closest('form');
    if (!opt || !form) return;
    const supId = opt.getAttribute('data-supplier');
    const bal = opt.getAttribute('data-balance');
    const supSelect = form.querySelector('select[name="supplier_id"]');
    const amtInput = form.querySelector('input[name="amount"]');
    if (supId && supSelect) supSelect.value = supId;
    if (bal && amtInput && (!amtInput.value || parseFloat(amtInput.value) === 0)) amtInput.value = bal;
};
</script>
@endsection
