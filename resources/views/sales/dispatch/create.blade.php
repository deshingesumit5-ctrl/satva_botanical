@extends('layouts.app')
@section('title', 'New Dispatch Challan')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Generate Delivery Challan (Dispatch)</h2>
            <p class="page-subtitle">Allocate finished goods stock lots and generate gate clearance documentation.</p>
        </div>
        <a href="{{ route('sales.dispatch.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.dispatch.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label required">Linked Sales Order</label>
                        <select name="sales_order_id" class="form-select" onchange="loadDispatchOrder(this)" required>
                            <option value="">Select Sales Order</option>
                            @foreach($orders as $o)
                                <option value="{{ $o->id }}" {{ $selectedOrder && $selectedOrder->id == $o->id ? 'selected' : '' }}>
                                    {{ $o->so_number }} | {{ $o->customer->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select Customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $selectedOrder && $selectedOrder->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Dispatch Date</label>
                        <input type="date" name="dispatch_date" class="form-control" required value="{{ old('dispatch_date', date('Y-m-d')) }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Transporter Name</label>
                        <input type="text" name="transporter_name" class="form-control" placeholder="e.g. VRL Logistics">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vehicle Registration No</label>
                        <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. MH 12 PQ 5566">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">LR / Bilty Number</label>
                        <input type="text" name="lr_number" class="form-control" placeholder="e.g. LR-998811">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional dispatch / packing remarks">
                </div>

                @if($selectedOrder)
                @php
                    $pendingItems = $selectedOrder->items->filter(fn($it) => (float)$it->quantity - (float)$it->dispatched_qty > 0)->values();
                @endphp
                <h6 class="fw-bold mb-3">Order Items to Ship</h6>
                @if($pendingItems->isEmpty())
                    <div class="alert alert-info small">All items on this Sales Order have already been fully dispatched.</div>
                @else
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Pending Qty</th>
                                <th width="280">Select Finished Lot</th>
                                <th width="160">Dispatch Qty *</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingItems as $idx => $it)
                            @php
                                $lots = $availableLots->get($it->product_id, collect());
                                $pendingQty = max(0, (float)$it->quantity - (float)$it->dispatched_qty);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $it->product->name ?? $it->item_name ?? '-' }}</div>
                                    <input type="hidden" name="items[{{ $idx }}][sales_order_item_id]" value="{{ $it->id }}">
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $it->product_id }}">
                                    <input type="hidden" name="items[{{ $idx }}][uom_id]" value="{{ $it->uom_id }}">
                                </td>
                                <td>{{ $pendingQty }} {{ $it->uom->symbol ?? '' }}</td>
                                <td>
                                    <select name="items[{{ $idx }}][stock_lot_id]" class="form-select form-select-sm" required>
                                        <option value="">Select Lot</option>
                                        @foreach($lots as $lot)
                                            <option value="{{ $lot->id }}">{{ $lot->lot_number }} (Avail: {{ $lot->available_qty }} {{ $lot->uom->symbol ?? '' }})</option>
                                        @endforeach
                                    </select>
                                    @if($lots->isEmpty())
                                        <small class="text-danger">No QC-approved FG stock lots available for this product.</small>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" step="0.001" min="0.001" max="{{ $pendingQty }}" name="items[{{ $idx }}][dispatched_qty]" class="form-control form-control-sm" value="{{ $pendingQty }}" required>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
                @else
                    <div class="alert alert-light border small text-muted mb-4">
                        <i class="bi bi-info-circle me-1"></i> Select a Confirmed Sales Order above to load items and allocate finished goods stock lots.
                    </div>
                @endif

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.dispatch.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Confirm Dispatch & Issue Gate Pass</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.loadDispatchOrder = function (selectEl) {
    const baseUrl = '{{ route('sales.dispatch.create') }}';
    const targetUrl = selectEl.value ? (baseUrl + '?order_id=' + encodeURIComponent(selectEl.value)) : baseUrl;
    if (selectEl.closest('#sattvaDynamicFormModal') && typeof window.openUrlInFormModal === 'function') {
        window.openUrlInFormModal(targetUrl);
    } else {
        window.location.href = targetUrl;
    }
};
</script>
@endsection
