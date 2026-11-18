@extends('layouts.app')
@section('title', 'Monthly Overhead Expenses')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Monthly Overhead & Utility Expenses</h2>
            <p class="page-subtitle">Electricity, boiler fuel (briquet/coal), direct plant labor, and administrative overheads for batch costing.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('costing.allocation.index') }}" class="btn btn-outline-info"><i class="bi bi-pie-chart me-1"></i> Cost Allocation</a>
            <a href="{{ route('costing.expenses.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-plus-lg me-1"></i> Log Expense Voucher
            </a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('costing.expenses.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        @foreach(($categories ?? \App\Models\MonthlyExpense::CATEGORIES) as $key => $label)
                            <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="month" class="form-select">
                        <option value="">All Months</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="year" class="form-select">
                        <option value="">All Years</option>
                        @foreach(($years ?? [date('Y')]) as $yr)
                            <option value="{{ $yr }}" {{ (string) request('year') === (string) $yr ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Posted" {{ request('status') === 'Posted' ? 'selected' : '' }}>Posted</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('costing.expenses.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Voucher No</th>
                        <th>Month / Year</th>
                        <th>Expense Category</th>
                        <th>Plant Facility</th>
                        <th>Amount (₹)</th>
                        <th>Status</th>
                        <th width="100" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $idx => $exp)
                    <tr>
                        <td>{{ $expenses->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $exp->expense_number ?? 'EXP-'.str_pad($exp->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ date('M', mktime(0,0,0,$exp->month,1)) }} {{ $exp->year }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $exp->expense_category }}</span></td>
                        <td>{{ $exp->plant->name ?? 'All Plants' }}</td>
                        <td class="fw-bold text-dark">₹{{ number_format($exp->amount, 2) }}</td>
                        <td>
                            @if($exp->status === 'Draft')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3">Draft</span>
                            @elseif($exp->status === 'Posted')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3">Posted</span>
                            @else
                                <span class="badge-status badge-status-{{ strtolower($exp->status) }}">{{ $exp->status }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                @if($exp->status === 'Draft')
                                    <form action="{{ route('costing.expenses.approve', $exp) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-icon text-success" title="Approve Voucher"><i class="bi bi-check-circle"></i></button>
                                    </form>
                                @endif
                                <a href="{{ route('costing.expenses.show', $exp) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="View Voucher"><i class="bi bi-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No monthly expenses logged.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($expenses->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $expenses->firstItem() }} to {{ $expenses->lastItem() }} of {{ $expenses->total() }}</span>
            {{ $expenses->links() }}
        </div>
        @endif
    </div>
</div>
@endsection