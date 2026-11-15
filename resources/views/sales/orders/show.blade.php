@extends('layouts.app')
@section('title', 'SO: ' . $order->order_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $order->order_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $order->status)) }}">{{ $order->status }}</span>
            </div>
            <p class="page-subtitle">Customer: <strong>{{ $order->customer->name ?? '-' }}</strong> | Date: {{ $order->created_at->format('d-m-Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($order->status === 'Confirmed' || $order->status === 'In Progress')
                <a href="{{ route('sales.dispatch.create', ['order_id' => $order->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-truck me-1"></i> Create Dispatch Note</a>
            @endif
            <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Order Items & Delivery Progress</h6></div>
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
                            <td class="fw-bold">{{ $it->product->name ?? '-' }}</td>
                            <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                            <td><span class="badge {{ $it->dispatched_quantity >= $it->quantity ? 'bg-success' : 'bg-warning text-dark' }}">{{ $it->dispatched_quantity ?? 0 }}</span></td>
                            <td>₹{{ number_format($it->unit_price, 2) }}</td>
                            <td>{{ $it->tax_percent }}%</td>
                            <td class="text-end fw-bold">₹{{ number_format($it->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="6" class="text-end fw-bold">Grand Total Value:</td>
                            <td class="text-end fw-bold text-success fs-5">₹{{ number_format($order->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection