@extends('layouts.app')
@section('title', 'Invoice: ' . $invoice->invoice_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $invoice->invoice_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $invoice->status)) }}">{{ $invoice->status }}</span>
            </div>
            <p class="page-subtitle">Customer: <strong>{{ $invoice->customer->name ?? '-' }}</strong> | Date: {{ $invoice->created_at->format('d-m-Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sales.invoices.pdf', $invoice) }}" class="btn btn-outline-info"><i class="bi bi-printer me-1"></i> Download PDF</a>
            <a href="{{ route('sales.receipts.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-cash-stack me-1"></i> Receive Payment</a>
            <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Tax Invoice Items</h6></div>
        <div class="card-sattva-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product Description</th>
                            <th>Quantity</th>
                            <th>Unit Rate (₹)</th>
                            <th>GST %</th>
                            <th class="text-end">Total Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $idx => $it)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-bold">{{ $it->product->name ?? '-' }}</td>
                            <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                            <td>₹{{ number_format($it->unit_price, 2) }}</td>
                            <td>{{ $it->tax_percent }}%</td>
                            <td class="text-end fw-bold">₹{{ number_format($it->total_amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold">Invoice Total:</td>
                            <td class="text-end fw-bold text-success fs-5">₹{{ number_format($invoice->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection