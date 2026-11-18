@extends('layouts.app')
@section('title', 'Expense Voucher')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Expense Voucher: {{ $expense->expense_number ?? 'EXP-'.$expense->id }}</h2>
            <p class="page-subtitle">Period: {{ date('F', mktime(0,0,0,$expense->month,1)) }} {{ $expense->year }} | Status: <strong>{{ $expense->status }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            @if($expense->status === 'Draft')
                <form action="{{ route('costing.expenses.approve', $expense) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Approve Voucher</button>
                </form>
            @elseif($expense->status === 'Approved')
                <a href="{{ route('costing.allocation.index') }}" class="btn btn-primary-sattva">
                    <i class="bi bi-pie-chart me-1"></i> Allocate to Batches
                </a>
            @endif
            <a href="{{ route('costing.expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><label class="text-muted small">Category</label><div class="fw-bold">{{ $expense->expense_category }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Amount</label><div class="fw-bold text-success fs-5">₹{{ number_format($expense->amount, 2) }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Plant</label><div class="fw-bold">{{ $expense->plant->name ?? 'All Plants' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Entry Date</label><div class="fw-bold">{{ $expense->entry_date ? $expense->entry_date->format('d-m-Y') : '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Logged By</label><div class="fw-bold">{{ $expense->enteredByUser->name ?? 'System' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Approved By</label><div class="fw-bold">{{ $expense->approvedByUser->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Posted At</label><div class="fw-bold">{{ $expense->posted_at ? $expense->posted_at->format('d-m-Y H:i') : '-' }}</div></div>
                <div class="col-md-12"><label class="text-muted small">Bill Reference / Remarks</label><div class="fw-normal">{{ $expense->description ?: '-' }}</div></div>
            </div>
        </div>
    </div>
</div>
@endsection