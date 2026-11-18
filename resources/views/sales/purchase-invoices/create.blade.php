@extends('layouts.app')
@section('title', 'Log Vendor Invoice')
@section('content')
<div class="container-fluid px-0" style="max-width: 750px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Log Vendor Purchase Bill</h2>
            <p class="page-subtitle">Verify supplier bill against inward GRN record.</p>
        </div>
        <a href="{{ route('sales.purchase-invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.purchase-invoices.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Supplier / Vendor</label>
                        <select name="supplier_id" id="piSupplierSelect" class="form-select" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Inward GRN Reference</label>
                        <select name="grn_id" class="form-select" onchange="syncPiSupplierFromGrn(this)">
                            <option value="">Select GRN (Optional)</option>
                            @foreach($grns as $g)
                                <option value="{{ $g->id }}" data-supplier="{{ $g->supplier_id }}">{{ $g->grn_number }} ({{ $g->supplier->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Vendor Invoice Number</label>
                        <input type="text" name="supplier_invoice_number" class="form-control" required placeholder="e.g. VEN-INV-8899">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Taxable Bill Amount (₹)</label>
                        <input type="number" step="0.01" min="0" name="taxable_amount" class="form-control" required placeholder="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Other Charges (₹)</label>
                        <input type="number" step="0.01" min="0" name="other_charges" class="form-control" value="0.00">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Total Bill Amount (₹)</label>
                        <input type="number" step="0.01" min="0.01" name="total_amount" class="form-control" required placeholder="0.00">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.purchase-invoices.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Vendor Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.syncPiSupplierFromGrn = function (selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const supId = opt ? opt.getAttribute('data-supplier') : '';
    const form = selectEl.closest('form');
    const supSelect = form ? form.querySelector('select[name="supplier_id"]') : null;
    if (supId && supSelect) {
        supSelect.value = supId;
    }
};
</script>
@endsection
