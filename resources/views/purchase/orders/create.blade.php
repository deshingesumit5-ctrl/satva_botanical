@extends('layouts.app')
@section('title', 'Create Purchase Order')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Generate Purchase Order</h2>
            <p class="page-subtitle">Commercial procurement order with item rates, GST and delivery terms.</p>
        </div>
        <a href="{{ route('purchase.orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    @if($fromPr)
        <div class="alert alert-info mb-4">
            <i class="bi bi-link-45deg me-1"></i> Generating PO against approved Indent <strong>{{ $fromPr->pr_number }}</strong>.
        </div>
    @endif

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('purchase.orders.store') }}" method="POST" id="poForm">
                @csrf
                @if($fromPr)
                    <input type="hidden" name="pr_id" value="{{ $fromPr->id }}">
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Supplier / Vendor</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->supplier_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Delivery Plant</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}" {{ $fromPr && $fromPr->plant_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+10 days')) }}">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Payment Terms</label>
                        <input type="text" name="payment_terms" class="form-control" value="Net 30 Days">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Delivery Terms / Remarks</label>
                        <input type="text" name="delivery_terms" class="form-control" placeholder="Door delivery to plant, Certificate of Analysis (CoA) required...">
                    </div>
                </div>

                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                    <span>Order Items & Pricing</span>
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
                            @if($fromPr && $fromPr->items->count())
                                @foreach($fromPr->items as $idx => $prItem)
                                <tr>
                                    <td>
                                        <select name="items[{{ $idx }}][material_id]" class="form-select form-select-sm" required>
                                            @foreach($materials as $m)
                                                <option value="{{ $m->id }}" {{ $prItem->material_id == $m->id ? 'selected' : '' }}>{{ $m->name }} ({{ $m->material_code }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.001" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" value="{{ $prItem->quantity }}" required></td>
                                    <td>
                                        <select name="items[{{ $idx }}][uom_id]" class="form-select form-select-sm" required>
                                            @foreach($uoms as $u)
                                                <option value="{{ $u->id }}" {{ $prItem->uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm" value="0.00" required></td>
                                    <td><input type="number" step="0.01" name="items[{{ $idx }}][tax_percent]" class="form-control form-control-sm" value="18.00"></td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>
                                        <select name="items[0][material_id]" class="form-select form-select-sm" required>
                                            <option value="">Select Material</option>
                                            @foreach($materials as $m)
                                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->material_code }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
                                    <td>
                                        <select name="items[0][uom_id]" class="form-select form-select-sm" required>
                                            @foreach($uoms as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" step="0.01" name="items[0][unit_price]" class="form-control form-control-sm" required placeholder="Price per unit"></td>
                                    <td><input type="number" step="0.01" name="items[0][tax_percent]" class="form-control form-control-sm" value="18.00"></td>
                                    <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase.orders.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Generate Purchase Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let poIdx = 10;
function addPoRow() {
    const tbody = document.getElementById('poItemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${poIdx}][material_id]" class="form-select form-select-sm" required>
                <option value="">Select Material</option>
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