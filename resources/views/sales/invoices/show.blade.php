@extends('layouts.app')
@section('title', 'Invoice: ' . $invoice->invoice_number)
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h2 class="page-title mb-0">{{ $invoice->invoice_number }}</h2>
                    <span
                        class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $invoice->payment_status)) }}">{{ $invoice->payment_status }}</span>
                </div>
                <p class="page-subtitle">
                    Customer: <strong>{{ $invoice->customer->name ?? '-' }}</strong>
                    | Date:
                    {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') : $invoice->created_at->format('d-m-Y') }}
                    @if($invoice->due_date)
                        | Due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') }}
                    @endif
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('sales.invoices.pdf', $invoice) }}" class="btn btn-outline-info"><i
                        class="bi bi-printer me-1"></i> Download PDF</a>
                @if($invoice->payment_status !== 'Paid')
                    <a href="{{ route('sales.receipts.create', ['invoice_id' => $invoice->id]) }}"
                        class="btn btn-primary-sattva"><i class="bi bi-cash-stack me-1"></i> Receive Payment</a>
                @endif
                <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary"><i
                        class="bi bi-arrow-left me-1"></i> Back</a>
            </div>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Tax Invoice Items</h6>
                <span class="small text-muted">
                    Paid: <strong class="text-success">₹{{ number_format($invoice->paid_amount, 2) }}</strong>
                    | Balance Due: <strong class="text-danger">₹{{ number_format($invoice->balance_amount, 2) }}</strong>
                </span>
            </div>
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
                                    <td class="fw-bold">{{ $it->product->name ?? $it->item_name ?? '-' }}</td>
                                    <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                                    <td>₹{{ number_format($it->rate, 2) }}</td>
                                    <td>{{ $it->gst_rate }}%</td>
                                    <td class="text-end fw-bold">₹{{ number_format($it->total_amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="5" class="text-end">Taxable Subtotal:</td>
                                <td class="text-end fw-semibold">₹{{ number_format($invoice->taxable_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end">GST Total:</td>
                                <td class="text-end fw-semibold">₹{{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end fw-bold">Invoice Total:</td>
                                <td class="text-end fw-bold text-success fs-5">
                                    ₹{{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection