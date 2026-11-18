@extends('layouts.app')
@section('title', 'Credit / Debit Notes')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Credit & Debit Notes</h2>
            <p class="page-subtitle">Commercial price adjustments, sales returns, and vendor rate differences.</p>
        </div>
        <a href="{{ route('sales.notes.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Issue Credit/Debit Note
        </a>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Note Number</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Party</th>
                        <th>Invoice Ref</th>
                        <th>Reason</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $idx => $n)
                    <tr>
                        <td>{{ $notes->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $n->note_number }}</td>
                        <td>{{ $n->note_date ? \Carbon\Carbon::parse($n->note_date)->format('d-m-Y') : $n->created_at->format('d-m-Y') }}</td>
                        <td><span class="badge {{ $n->note_type === 'Credit Note' ? 'bg-primary' : 'bg-warning text-dark' }}">{{ $n->note_type }}</span></td>
                        <td>{{ $n->customer->name ?? $n->supplier->name ?? '-' }} <span class="text-muted small">({{ $n->party_type }})</span></td>
                        <td><code>{{ $n->invoice_reference ?? '-' }}</code></td>
                        <td>{{ $n->reason }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($n->amount ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No credit or debit notes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($notes->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $notes->firstItem() }} to {{ $notes->lastItem() }} of {{ $notes->total() }}</span>
            {{ $notes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
