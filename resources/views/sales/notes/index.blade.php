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
                        <th>Type</th>
                        <th>Party</th>
                        <th>Reason</th>
                        <th class="text-end">Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $idx => $n)
                    <tr>
                        <td>{{ $notes->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $n->note_number }}</td>
                        <td><span class="badge {{ $n->note_type === 'Credit' ? 'bg-primary' : 'bg-warning text-dark' }}">{{ $n->note_type }} Note</span></td>
                        <td>{{ $n->customer->name ?? $n->supplier->name ?? '-' }}</td>
                        <td>{{ $n->reason }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($n->total_amount ?? $n->amount ?? 0, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No credit or debit notes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection