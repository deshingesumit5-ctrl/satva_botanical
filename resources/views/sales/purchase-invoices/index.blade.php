@extends('layouts.app')
@section('title', 'Purchase Invoices (Vendor Bills)')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Vendor Invoices & Purchase Bills</h2>
            <p class="page-subtitle">Accounts payable verification against Goods Receipt Notes (GRN 3-way match).</p>
        </div>
        <a href="{{ route('sales.purchase-invoices.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Log Vendor Invoice
        </a>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Bill Reference</th>
                        <th>Invoice No</th>
                        <th>Supplier</th>
                        <th>GRN Number</th>
                        <th>Bill Amount (₹)</th>
                        <th>Status</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $idx => $pi)
                    <tr>
                        <td>{{ $invoices->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $pi->bill_number ?? 'BILL-'.str_pad($pi->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $pi->invoice_number }}</td>
                        <td class="fw-semibold text-dark">{{ $pi->supplier->name ?? '-' }}</td>
                        <td>{{ $pi->grn->grn_number ?? '-' }}</td>
                        <td class="fw-bold">₹{{ number_format($pi->total_amount, 2) }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($pi->status) }}">{{ $pi->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('sales.purchase-invoices.show', $pi) }}" class="btn btn-sm btn-icon btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No vendor purchase bills recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }}</span>
            {{ $invoices->links() }}
        </div>
        @endif
    </div>
</div>
@endsection