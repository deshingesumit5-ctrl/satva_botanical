@extends('layouts.app')
@section('title', 'Supplier Payments')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Supplier Payments Register</h2>
            <p class="page-subtitle">Bank transfers, RTGS and cheques issued to raw material vendors.</p>
        </div>
        <a href="{{ route('sales.payments.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Make Payment
        </a>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Voucher No</th>
                        <th>Payment Date</th>
                        <th>Supplier</th>
                        <th>Payment Mode</th>
                        <th>Reference / UTR</th>
                        <th class="text-end">Amount Paid (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $idx => $pay)
                    <tr>
                        <td>{{ $payments->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $pay->voucher_number ?? 'PMT-'.str_pad($pay->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $pay->payment_date ? \Carbon\Carbon::parse($pay->payment_date)->format('d-m-Y') : $pay->created_at->format('d-m-Y') }}</td>
                        <td class="fw-semibold text-dark">{{ $pay->supplier->name ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $pay->payment_mode ?? 'NEFT/RTGS' }}</span></td>
                        <td><code>{{ $pay->reference_number ?? '-' }}</code></td>
                        <td class="text-end fw-bold text-success">₹{{ number_format($pay->amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No payments recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($payments->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $payments->firstItem() }} to {{ $payments->lastItem() }} of {{ $payments->total() }}</span>
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection