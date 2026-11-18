@extends('layouts.app')
@section('title', 'Issue Credit/Debit Note')
@section('content')
    <div class="container-fluid px-0" style="max-width: 650px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Issue Credit / Debit Note</h2>
                <p class="page-subtitle">Adjustment note against customer or supplier.</p>
            </div>
            <a href="{{ route('sales.notes.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('sales.notes.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label required">Note Type</label>
                            <select name="note_type" class="form-select" required>
                                <option value="Credit Note">Credit Note</option>
                                <option value="Debit Note">Debit Note</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Party Type</label>
                            <select name="party_type" class="form-select" onchange="toggleCdnParty(this)" required>
                                <option value="Customer">Customer</option>
                                <option value="Supplier">Supplier</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Note Date</label>
                            <input type="date" name="note_date" class="form-control" required
                                value="{{ old('note_date', date('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6" id="cdnCustomerWrap">
                            <label class="form-label required">Customer</label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-none" id="cdnSupplierWrap">
                            <label class="form-label required">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">Select Supplier</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Adjusted Amount (₹)</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required
                                placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Original Invoice Reference</label>
                        <input type="text" name="invoice_reference" class="form-control" placeholder="e.g. INV-2026-0001">
                    </div>

                    <div class="mb-4">
                        <label class="form-label required">Reason / Justification</label>
                        <textarea name="reason" class="form-control" rows="3" required
                            placeholder="Reason for rate adjustment, return, or quality discount..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('sales.notes.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary-sattva">Save Note</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.toggleCdnParty = function (selectEl) {
            const form = selectEl.closest('form');
            if (!form) return;
            const custWrap = form.querySelector('#cdnCustomerWrap');
            const supWrap = form.querySelector('#cdnSupplierWrap');
            const custSel = form.querySelector('select[name="customer_id"]');
            const supSel = form.querySelector('select[name="supplier_id"]');
            if (selectEl.value === 'Supplier') {
                custWrap.classList.add('d-none');
                supWrap.classList.remove('d-none');
                if (custSel) { custSel.removeAttribute('required'); custSel.value = ''; }
                if (supSel) supSel.setAttribute('required', 'required');
            } else {
                supWrap.classList.add('d-none');
                custWrap.classList.remove('d-none');
                if (supSel) { supSel.removeAttribute('required'); supSel.value = ''; }
                if (custSel) custSel.setAttribute('required', 'required');
            }
        };
    </script>
@endsection