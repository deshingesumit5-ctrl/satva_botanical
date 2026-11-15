@extends('layouts.app')
@section('title', 'PO: ' . $order->po_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $order->po_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $order->status)) }}">{{ $order->status }}</span>
            </div>
            <p class="page-subtitle">Supplier: {{ $order->supplier->name ?? 'None' }} | Date: {{ $order->created_at->format('d-m-Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchase.orders.print', $order) }}" class="btn btn-outline-info"><i class="bi bi-printer me-1"></i> Print / PDF</a>

            @if($order->status === 'Draft')
                <form action="{{ route('purchase.orders.approve', $order) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="button" class="btn btn-success" onclick="confirmAction('Approve this purchase order?', this.closest('form'))">Approve Order</button>
                </form>
            @endif

            @if($order->status === 'Approved' || $order->status === 'Partially Received')
                <a href="{{ route('purchase.grn.from-po', $order) }}" class="btn btn-primary-sattva"><i class="bi bi-box-arrow-in-down me-1"></i> Inward Goods (GRN)</a>
            @endif

            <a href="{{ route('purchase.orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Order Items & Specifications</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Ordered Qty</th>
                                    <th>Received Qty</th>
                                    <th>Rate (₹)</th>
                                    <th>Tax</th>
                                    <th class="text-end">Total (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $idx => $it)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $it->material->name ?? '-' }}</div>
                                        <small class="text-muted">{{ $it->material->material_code ?? '' }}</small>
                                    </td>
                                    <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                                    <td>
                                        <span class="badge {{ $it->received_quantity >= $it->quantity ? 'bg-success' : 'bg-warning text-dark' }}">{{ $it->received_quantity ?? 0 }}</span>
                                    </td>
                                    <td>₹{{ number_format($it->unit_price, 2) }}</td>
                                    <td>{{ $it->tax_percent }}%</td>
                                    <td class="text-end fw-bold">₹{{ number_format($it->total_amount, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="6" class="text-end fw-bold">Grand Total (Inclusive of Taxes):</td>
                                    <td class="text-end fw-bold text-success fs-5">₹{{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Vendor & Terms</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="mb-3">
                        <label class="text-muted small">Vendor Name</label>
                        <div class="fw-bold">{{ $order->supplier->name ?? '-' }}</div>
                        <div class="small text-muted">{{ $order->supplier->address_line ?? '' }}, {{ $order->supplier->city ?? '' }}</div>
                        <div class="small">GSTIN: <code>{{ $order->supplier->gstin ?? 'N/A' }}</code></div>
                    </div>
                    <hr>
                    <div class="row g-2">
                        <div class="col-6"><label class="text-muted small">Plant</label><div class="fw-semibold">{{ $order->plant->name ?? '-' }}</div></div>
                        <div class="col-6"><label class="text-muted small">Delivery Date</label><div class="fw-semibold">{{ $order->expected_delivery_date ? \Carbon\Carbon::parse($order->expected_delivery_date)->format('d-m-Y') : '-' }}</div></div>
                        <div class="col-12"><label class="text-muted small">Payment Terms</label><div class="fw-semibold">{{ $order->payment_terms ?? '-' }}</div></div>
                        <div class="col-12"><label class="text-muted small">Delivery Instructions</label><p class="small text-muted mb-0">{{ $order->delivery_terms ?? '-' }}</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection