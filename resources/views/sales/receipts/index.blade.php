@extends('layouts.app')
@section('title', 'Customer Receipts')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Customer Receipts & Payments Inward</h2>
            <p class="page-subtitle">Inward wire remittances, client bank transfers, and invoice reconciliation.</p>
        </div>
        <a href="{{ route('sales.receipts.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Receive Payment
        </a>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Receipt No</th>
                        <th>Receipt Date</th>
                        <th>Customer</th>
                        <th>Mode</th>
                        <th>Ref Number</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $idx => $rec)
                    <tr>
                        <td>{{ $receipts->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $rec->receipt_number ?? 'REC-'.str_pad($rec->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $rec->receipt_date ? \Carbon\Carbon::parse($rec->receipt_date)->format('d-m-Y') : $rec->created_at->format('d-m-Y') }}</td>
                        <td class="fw-semibold text-dark">{{ $rec->customer->name ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $rec->payment_mode ?? 'Bank Transfer' }}</span></td>
                        <td><code>{{ $rec->reference_number ?? '-' }}</code></td>
                        <td class="text-end fw-bold text-success">₹{{ number_format($rec->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No customer receipts recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($receipts->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $receipts->firstItem() }} to {{ $receipts->lastItem() }} of {{ $receipts->total() }}</span>
            {{ $receipts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection