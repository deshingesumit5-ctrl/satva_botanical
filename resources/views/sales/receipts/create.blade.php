@extends('layouts.app')
@section('title', 'Log Customer Receipt')
@section('content')
<div class="container-fluid px-0" style="max-width: 650px;">
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
                @php
                    $preSelectedInv = $selectedInvoiceId ? $invoices->firstWhere('id', (int)$selectedInvoiceId) : null;
                @endphp
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Sales Invoice</label>
                        <select name="sales_invoice_id" class="form-select" onchange="syncCustomerFromInvoice(this)" required>
                            <option value="">Select Sales Invoice</option>
                            @foreach($invoices as $inv)
                                <option value="{{ $inv->id }}"
                                    data-customer="{{ $inv->customer_id }}"
                                    data-balance="{{ number_format((float)$inv->balance_amount, 2, '.', '') }}"
                                    {{ (int)($selectedInvoiceId ?? 0) === $inv->id ? 'selected' : '' }}>
                                    {{ $inv->invoice_number }} | {{ $inv->customer->name ?? '' }} (Due: ₹{{ number_format($inv->balance_amount, 2) }})
                                </option>
                            @endforeach
                        </select>
                        @if($invoices->isEmpty())
                            <small class="text-warning">No unpaid sales invoices found.</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $preSelectedInv && $preSelectedInv->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Receipt Date</label>
                        <input type="date" name="receipt_date" class="form-control" required value="{{ old('receipt_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Amount Received (₹)</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required value="{{ $preSelectedInv ? number_format((float)$preSelectedInv->balance_amount, 2, '.', '') : '' }}" placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Payment Mode</label>
                        <select name="payment_mode" class="form-select" required>
                            <option value="Bank Transfer">Bank Transfer / NEFT</option>
                            <option value="Cheque">Cheque</option>
                            <option value="UPI">UPI</option>
                            <option value="Cash">Cash</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Reference / UTR / Cheque No</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. UTR998877">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Remarks</label>
                        <input type="text" name="remarks" class="form-control" placeholder="Optional receipt notes">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.receipts.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.syncCustomerFromInvoice = function (selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const form = selectEl.closest('form');
    if (!opt || !form) return;
    const custId = opt.getAttribute('data-customer');
    const bal = opt.getAttribute('data-balance');
    const custSelect = form.querySelector('select[name="customer_id"]');
    const amtInput = form.querySelector('input[name="amount"]');
    if (custId && custSelect) custSelect.value = custId;
    if (bal && amtInput && (!amtInput.value || parseFloat(amtInput.value) === 0)) amtInput.value = bal;
};
</script>
@endsection
