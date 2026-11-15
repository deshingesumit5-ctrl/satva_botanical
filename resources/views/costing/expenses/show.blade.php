@extends('layouts.app')
@section('title', 'Expense Voucher')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Expense Voucher: {{ $expense->voucher_no ?? 'EXP-'.$expense->id }}</h2>
            <p class="page-subtitle">Period: {{ date('F', mktime(0,0,0,$expense->month,1)) }} {{ $expense->year }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($expense->status === 'Pending')
                <form action="{{ route('costing.expenses.approve', $expense) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i> Approve Voucher</button>
                </form>
            @endif
            <a href="{{ route('costing.expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><label class="text-muted small">Category</label><div class="fw-bold">{{ $expense->expense_category }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Amount</label><div class="fw-bold text-success fs-5">₹{{ number_format($expense->amount, 2) }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Plant</label><div class="fw-bold">{{ $expense->plant->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Logged By</label><div class="fw-bold">{{ $expense->createdByUser->name ?? 'System' }}</div></div>
            </div>
        </div>
    </div>
</div>
@endsection