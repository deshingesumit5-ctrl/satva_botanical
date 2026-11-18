@extends('layouts.app')
@section('title', 'Sales Tax Invoices')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Sales Tax Invoices (GST)</h2>
                <p class="page-subtitle">Commercial GST billing, accounts receivable, and e-way bill references.</p>
            </div>
            <a href="{{ route('sales.invoices.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-receipt me-1"></i> New Sales Invoice
            </a>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('sales.invoices.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search invoice number, customer..."></div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Paid" {{ request('status') === 'Paid' ? 'selected' : '' }}>Paid</option>
                            <option value="Partially Paid" {{ request('status') === 'Partially Paid' ? 'selected' : '' }}>
                                Partially Paid</option>
                            <option value="Unpaid" {{ request('status') === 'Unpaid' ? 'selected' : '' }}>Unpaid</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                        <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-sattva">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Invoice Number</th>
                            <th>Invoice Date</th>
                            <th>Customer</th>
                            <th>Total (₹)</th>
                            <th>Paid (₹)</th>
                            <th>Due (₹)</th>
                            <th>Status</th>
                            <th width="140" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $idx => $inv)
                            <tr>
                                <td>{{ $invoices->firstItem() + $idx }}</td>
                                <td class="fw-bold font-monospace text-success">{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('d-m-Y') : $inv->created_at->format('d-m-Y') }}
                                </td>
                                <td class="fw-semibold text-dark">{{ $inv->customer->name ?? '-' }}</td>
                                <td class="fw-bold">₹{{ number_format($inv->total_amount, 2) }}</td>
                                <td class="text-success">₹{{ number_format($inv->paid_amount ?? 0, 2) }}</td>
                                <td class="text-danger fw-bold">
                                    ₹{{ number_format($inv->balance_amount ?? max(0, $inv->total_amount - ($inv->paid_amount ?? 0)), 2) }}
                                </td>
                                <td><span
                                        class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $inv->payment_status)) }}">{{ $inv->payment_status }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('sales.invoices.show', $inv) }}"
                                        class="btn btn-sm btn-icon btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('sales.invoices.pdf', $inv) }}"
                                        class="btn btn-sm btn-icon btn-outline-info" title="PDF Invoice"><i
                                            class="bi bi-printer"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No sales invoices recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($invoices->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of
                        {{ $invoices->total() }}</span>
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection