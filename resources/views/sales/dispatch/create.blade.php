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
                    <div class="col-md-6">
                        <label class="form-label required">Linked Sales Order</label>
                        <select name="sales_order_id" class="form-select" onchange="window.location='{{ route('sales.dispatch.create') }}?order_id=' + this.value" required>
                            <option value="">Select Sales Order</option>
                            @foreach($orders as $o)
                                <option value="{{ $o->id }}" {{ $selectedOrder && $selectedOrder->id == $o->id ? 'selected' : '' }}>
                                    {{ $o->order_number }} | {{ $o->customer->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $selectedOrder && $selectedOrder->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
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

                @if($selectedOrder)
                <h6 class="fw-bold mb-3">Order Items to Ship</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Order Qty</th>
                                <th width="250">Select Finished Lot</th>
                                <th width="150">Dispatch Qty *</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedOrder->items as $idx => $it)
                            @php
                                $lots = $availableLots[$it->product_id] ?? collect();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $it->product->name ?? '-' }}</div>
                                    <input type="hidden" name="items[{{ $idx }}][so_item_id]" value="{{ $it->id }}">
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $it->product_id }}">
                                    <input type="hidden" name="items[{{ $idx }}][uom_id]" value="{{ $it->uom_id }}">
                                </td>
                                <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                                <td>
                                    <select name="items[{{ $idx }}][stock_lot_id]" class="form-select form-select-sm" required>
                                        <option value="">Select Lot</option>
                                        @foreach($lots as $lot)
                                            <option value="{{ $lot->id }}">{{ $lot->lot_number }} (Avail: {{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.001" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" value="{{ $it->quantity - ($it->dispatched_quantity ?? 0) }}" required>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
@endsection