@extends('layouts.app')
@section('title', 'Commercial Sales & Purchase Report')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Commercial Revenue & Procurement Report</h2>
            <p class="page-subtitle">Sales turnover, GST liability summary, and vendor procurement totals.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card card-sattva p-4">
                <span class="text-muted small">Total Sales Revenue (Gross)</span>
                <div class="h2 fw-bold text-success mt-2 mb-0">₹{{ number_format($totalSales, 2) }}</div>
                <small class="text-muted">GST Collected: ₹{{ number_format($totalSalesTax, 2) }}</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card card-sattva p-4">
                <span class="text-muted small">Total Procurement Spend</span>
                <div class="h2 fw-bold text-dark mt-2 mb-0">₹{{ number_format($totalPurchases, 2) }}</div>
                <small class="text-muted">Input Tax Credit (ITC): ₹{{ number_format($totalPurchaseTax, 2) }}</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card card-sattva">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Recent Sales Invoices</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice No</th>
                                <th>Customer</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($salesInvoices as $si)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $si->invoice_number }}</td>
                                <td>{{ $si->customer->name ?? '-' }}</td>
                                <td class="text-end fw-bold">₹{{ number_format($si->total_amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-3 text-muted">No sales invoices.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-sattva">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Recent Vendor Bills</h6></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Bill No</th>
                                <th>Supplier</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseInvoices as $pi)
                            <tr>
                                <td class="fw-bold font-monospace">{{ $pi->invoice_number }}</td>
                                <td>{{ $pi->supplier->name ?? '-' }}</td>
                                <td class="text-end fw-bold">₹{{ number_format($pi->total_amount, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-3 text-muted">No purchase bills.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection