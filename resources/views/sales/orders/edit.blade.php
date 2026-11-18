@extends('layouts.app')
@section('title', 'Edit Sales Order')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Order: {{ $order->so_number }}</h2>
            <p class="page-subtitle">Update customer sales order lines and delivery dates.</p>
        </div>
        <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.orders.update', $order) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Customer / Client</label>
                        <select name="customer_id" class="form-select" required>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ old('customer_id', $order->customer_id) == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->customer_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Order Date</label>
                        <input type="date" name="order_date" class="form-control" required value="{{ old('order_date', $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('Y-m-d') : date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Committed Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control" required value="{{ old('delivery_date', $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Payment Terms & Instructions</label>
                    <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', $order->payment_terms) }}">
                </div>

                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                    <span>Order Line Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSoEditRow()"><i class="bi bi-plus-lg me-1"></i> Add Product</button>
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="38%">Finished Good Product</th>
                                <th width="14%">Order Qty</th>
                                <th width="16%">Unit (UOM)</th>
                                <th width="14%">Unit Rate (INR)</th>
                                <th width="10%">Tax %</th>
                                <th width="8%" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="soEditItemsBody">
                            @foreach($order->items as $idx => $item)
                            <tr>
                                <td>
                                    <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm" onchange="syncSoEditRowUom(this)" required>
                                        <option value="">Select Product</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}" data-uom="{{ $p->base_uom_id }}" {{ $item->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->product_code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" min="0.001" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" required value="{{ $item->quantity }}"></td>
                                <td>
                                    <select name="items[{{ $idx }}][uom_id]" class="form-select form-select-sm" required>
                                        @foreach($uoms as $u)
                                            <option value="{{ $u->id }}" {{ $item->uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm" required value="{{ $item->rate }}"></td>
                                <td><input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percent]" class="form-control form-control-sm" value="{{ $item->gst_rate }}"></td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeSoEditRow(this)"><i class="bi bi-trash"></i></button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.orders.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Sales Order?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.soEditIdx = {{ max(1, $order->items->count()) }};
window.syncSoEditRowUom = function (selectEl) {
    const opt = selectEl.options[selectEl.selectedIndex];
    const uomId = opt ? opt.getAttribute('data-uom') : '';
    const row = selectEl.closest('tr');
    const uomSelect = row ? row.querySelector('select[name$="[uom_id]"]') : null;
    if (uomId && uomSelect) {
        uomSelect.value = uomId;
    }
};
window.removeSoEditRow = function (btn) {
    const tbody = document.getElementById('soEditItemsBody');
    if (tbody && tbody.querySelectorAll('tr').length > 1) {
        btn.closest('tr').remove();
    }
};
window.addSoEditRow = function () {
    const tbody = document.getElementById('soEditItemsBody');
    const idx = window.soEditIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${idx}][product_id]" class="form-select form-select-sm" onchange="syncSoEditRowUom(this)" required>
                <option value="">Select Product</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" data-uom="{{ $p->base_uom_id }}">{{ $p->name }} ({{ $p->product_code }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" min="0.001" name="items[${idx}][quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
        <td>
            <select name="items[${idx}][uom_id]" class="form-select form-select-sm" required>
                @foreach($uoms as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.01" min="0" name="items[${idx}][unit_price]" class="form-control form-control-sm" required placeholder="Rate"></td>
        <td><input type="number" step="0.01" min="0" max="100" name="items[${idx}][tax_percent]" class="form-control form-control-sm" value="18.00"></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="removeSoEditRow(this)"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
};
</script>
@endsection
