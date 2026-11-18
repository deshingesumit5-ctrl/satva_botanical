@extends('layouts.app')
@section('title', 'SO: ' . $order->so_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $order->so_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $order->status)) }}">{{ $order->status }}</span>
            </div>
            <p class="page-subtitle">
                Customer: <strong>{{ $order->customer->name ?? '-' }}</strong>
                | Order Date: {{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d-m-Y') : $order->created_at->format('d-m-Y') }}
                @if($order->delivery_date)
                    | Delivery Date: {{ \Carbon\Carbon::parse($order->delivery_date)->format('d-m-Y') }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($order->status === 'Draft')
                <form action="{{ route('sales.orders.approve', $order) }}" method="POST" class="d-inline" id="confirmShowSoForm">
                    @csrf
                    <button type="button" class="btn btn-primary-sattva"
                        onclick="confirmAction('Confirm Sales Order {{ $order->so_number }} and release for dispatch?', 'confirmShowSoForm', { title: 'Confirm Sales Order', confirmText: 'Yes, Confirm' })">
                        <i class="bi bi-check-lg me-1"></i> Confirm Order
                    </button>
                </form>
                <a href="{{ route('sales.orders.edit', $order) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
            @endif
            @if(in_array($order->status, ['Confirmed', 'In Progress', 'Partially Dispatched']))
                <a href="{{ route('sales.dispatch.create', ['order_id' => $order->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-truck me-1"></i> Create Dispatch Note</a>
            @endif
            <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold">Order Items & Delivery Progress</h6>
            @if($order->payment_terms)
                <span class="text-muted small">Terms: <strong>{{ $order->payment_terms }}</strong></span>
            @endif
        </div>
        <div class="card-sattva-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product Description</th>
                            <th>Ordered Qty</th>
                            <th>Shipped Qty</th>
                            <th>Unit Price (₹)</th>
                            <th>Tax %</th>
                            <th class="text-end">Total (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $idx => $it)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-bold">{{ $it->product->name ?? $it->item_name ?? '-' }}</td>
                            <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                            <td><span class="badge {{ $it->dispatched_qty >= $it->quantity ? 'bg-success' : 'bg-warning text-dark' }}">{{ $it->dispatched_qty ?? 0 }}</span></td>
                            <td>₹{{ number_format($it->rate, 2) }}</td>
                            <td>{{ $it->gst_rate }}%</td>
                            <td class="text-end fw-bold">₹{{ number_format($it->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="6" class="text-end">Taxable Subtotal:</td>
                            <td class="text-end fw-semibold">₹{{ number_format($order->taxable_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end">GST Total:</td>
                            <td class="text-end fw-semibold">₹{{ number_format($order->tax_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="6" class="text-end fw-bold">Grand Total Value:</td>
                            <td class="text-end fw-bold text-success fs-5">₹{{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    @if($order->dispatches->count() > 0)
    <div class="card card-sattva">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Linked Dispatch Notes</h6></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dispatch No</th>
                        <th>Dispatch Date</th>
                        <th>Transport / Vehicle</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->dispatches as $dsp)
                    <tr>
                        <td class="fw-bold text-success">{{ $dsp->dispatch_number }}</td>
                        <td>{{ $dsp->dispatch_date ? \Carbon\Carbon::parse($dsp->dispatch_date)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $dsp->transport_details ?? '-' }} ({{ $dsp->vehicle_number ?? '-' }})</td>
                        <td><span class="badge-status">{{ $dsp->status }}</span></td>
                        <td class="text-end"><a href="{{ route('sales.dispatch.show', $dsp) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
