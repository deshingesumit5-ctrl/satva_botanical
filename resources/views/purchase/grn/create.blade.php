@extends('layouts.app')
@section('title', 'Direct Goods Receipt Note (GRN)')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Direct Inward Goods Receipt (GRN)</h2>
            <p class="page-subtitle">Receive materials without pre-existing PO or from emergency spot vendor deliveries.</p>
        </div>
        <a href="{{ route('purchase.grn.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('purchase.grn.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Supplier</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Delivery Plant</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Destination Quarantine Warehouse</label>
                        <select name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->type }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Vendor Invoice / DC Number</label>
                        <input type="text" name="invoice_number" class="form-control" required placeholder="e.g. INV-2026-991">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vehicle Registration Number</label>
                        <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. MH 12 AB 1234">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gate Pass Entry Number</label>
                        <input type="text" name="gate_pass_number" class="form-control" placeholder="e.g. GP-5542">
                    </div>
                </div>

                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                    <span>Received Materials</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addGrnRow()"><i class="bi bi-plus-lg me-1"></i> Add Material</button>
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="35%">Material</th>
                                <th width="15%">Vendor Lot No</th>
                                <th width="15%">Received Qty</th>
                                <th width="15%">Unit (UOM)</th>
                                <th width="10%">Storage Rack</th>
                                <th width="10%" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="grnItemsBody">
                            <tr>
                                <td>
                                    <select name="items[0][material_id]" class="form-select form-select-sm" required>
                                        <option value="">Select Material</option>
                                        @foreach($materials as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->material_code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="text" name="items[0][vendor_lot_number]" class="form-control form-control-sm" placeholder="Vendor Lot #"></td>
                                <td><input type="number" step="0.001" name="items[0][received_quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
                                <td>
                                    <select name="items[0][uom_id]" class="form-select form-select-sm" required>
                                        @foreach($uoms as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="items[0][storage_location_id]" class="form-select form-select-sm">
                                        <option value="">Default</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase.grn.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Post GRN & Inward to Quarantine</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let grnIdx = 1;
function addGrnRow() {
    const tbody = document.getElementById('grnItemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${grnIdx}][material_id]" class="form-select form-select-sm" required>
                <option value="">Select Material</option>
                @foreach($materials as $m)
                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->material_code }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" name="items[${grnIdx}][vendor_lot_number]" class="form-control form-control-sm" placeholder="Vendor Lot #"></td>
        <td><input type="number" step="0.001" name="items[${grnIdx}][received_quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
        <td>
            <select name="items[${grnIdx}][uom_id]" class="form-select form-select-sm" required>
                @foreach($uoms as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                @endforeach
            </select>
        </td>
        <td>
            <select name="items[${grnIdx}][storage_location_id]" class="form-select form-select-sm">
                <option value="">Default</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    grnIdx++;
}
</script>
@endsection