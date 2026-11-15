@extends('layouts.app')
@section('title', 'Edit Purchase Order')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Order: {{ $order->po_number }}</h2>
            <p class="page-subtitle">Update vendor order items, quantities and commercial terms.</p>
        </div>
        <a href="{{ route('purchase.orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('purchase.orders.update', $order) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ $order->supplier_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Plant</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}" {{ $order->plant_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery_date" class="form-control" required value="{{ old('expected_delivery_date', $order->expected_delivery_date ? \Carbon\Carbon::parse($order->expected_delivery_date)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Payment Terms</label>
                        <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', $order->payment_terms) }}">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Delivery Terms / Remarks</label>
                        <input type="text" name="delivery_terms" class="form-control" value="{{ old('delivery_terms', $order->delivery_terms) }}">
                    </div>
                </div>

                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                    <span>Order Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPoRow()"><i class="bi bi-plus-lg me-1"></i> Add Material</button>
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="35%">Material</th>
                                <th width="15%">Order Qty</th>
                                <th width="15%">Unit (UOM)</th>
                                <th width="15%">Unit Rate (INR)</th>
                                <th width="10%">Tax %</th>
                                <th width="10%" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="poItemsBody">
                            @foreach($order->items as $idx => $item)
                            <tr>
                                <td>
                                    <select name="items[{{ $idx }}][material_id]" class="form-select form-select-sm" required>
                                        @foreach($materials as $m)
                                            <option value="{{ $m->id }}" {{ $item->material_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" value="{{ $item->quantity }}" required></td>
                                <td>
                                    <select name="items[{{ $idx }}][uom_id]" class="form-select form-select-sm" required>
                                        @foreach($uoms as $u)
                                            <option value="{{ $u->id }}" {{ $item->uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm" value="{{ $item->unit_price }}" required></td>
                                <td><input type="number" step="0.01" name="items[{{ $idx }}][tax_percent]" class="form-control form-control-sm" value="{{ $item->tax_percent }}"></td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase.orders.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Purchase Order?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let poIdx = {{ $order->items->count() + 1 }};
function addPoRow() {
    const tbody = document.getElementById('poItemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${poIdx}][material_id]" class="form-select form-select-sm" required>
                @foreach($materials as $m)
                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->material_code }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" name="items[${poIdx}][quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
        <td>
            <select name="items[${poIdx}][uom_id]" class="form-select form-select-sm" required>
                @foreach($uoms as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.01" name="items[${poIdx}][unit_price]" class="form-control form-control-sm" required placeholder="Price"></td>
        <td><input type="number" step="0.01" name="items[${poIdx}][tax_percent]" class="form-control form-control-sm" value="18.00"></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    poIdx++;
}
</script>
@endsection