@extends('layouts.app')
@section('title', 'Inward GRN from PO: ' . $order->po_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Receive PO Goods: {{ $order->po_number }}</h2>
            <p class="page-subtitle">Supplier: <strong>{{ $order->supplier->name ?? '-' }}</strong> | Plant: {{ $order->plant->name ?? '-' }}</p>
        </div>
        <a href="{{ route('purchase.orders.show', $order) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to PO</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('purchase.grn.store-from-po', $order) }}" method="POST">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Destination Quarantine Warehouse</label>
                        <select name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->type }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Vendor Invoice / DC Number</label>
                        <input type="text" name="invoice_number" class="form-control" required placeholder="e.g. INV-9901">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vehicle Registration Number</label>
                        <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. MH 14 GH 7890">
                    </div>
                </div>

                <h6 class="fw-bold mb-3">PO Line Items Receiving</h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Material</th>
                                <th>Ordered</th>
                                <th>Already Recv</th>
                                <th width="150">Receiving Qty *</th>
                                <th width="150">Vendor Lot #</th>
                                <th width="150">Storage Rack</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $idx => $it)
                            @php
                                $pending = max(0, $it->quantity - ($it->received_quantity ?? 0));
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $it->material->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $it->material->material_code ?? '' }}</small>
                                    <input type="hidden" name="items[{{ $idx }}][po_item_id]" value="{{ $it->id }}">
                                    <input type="hidden" name="items[{{ $idx }}][material_id]" value="{{ $it->material_id }}">
                                    <input type="hidden" name="items[{{ $idx }}][uom_id]" value="{{ $it->uom_id }}">
                                </td>
                                <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                                <td>{{ $it->received_quantity ?? 0 }} {{ $it->uom->symbol ?? '' }}</td>
                                <td>
                                    <input type="number" step="0.001" name="items[{{ $idx }}][received_quantity]" class="form-control form-control-sm" value="{{ $pending }}" max="{{ $pending }}" required>
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $idx }}][vendor_lot_number]" class="form-control form-control-sm" placeholder="Lot / Batch #">
                                </td>
                                <td>
                                    <select name="items[{{ $idx }}][storage_location_id]" class="form-select form-select-sm">
                                        <option value="">Default Location</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase.orders.show', $order) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva"><i class="bi bi-box-arrow-in-down me-1"></i> Post GRN & Inward to Quarantine</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection