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
                        <option value="Electricity" {{ request('category') === 'Electricity' ? 'selected' : '' }}>Electricity (HT Power)</option>
                        <option value="Boiler Fuel" {{ request('category') === 'Boiler Fuel' ? 'selected' : '' }}>Boiler Fuel / Steam</option>
                        <option value="Direct Labor" {{ request('category') === 'Direct Labor' ? 'selected' : '' }}>Direct Factory Labor</option>
                        <option value="Solvent Losses" {{ request('category') === 'Solvent Losses' ? 'selected' : '' }}>Solvent Evaporation Losses</option>
                        <option value="QC & Lab Testing" {{ request('category') === 'QC & Lab Testing' ? 'selected' : '' }}>QC Consumables</option>
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
                        <option value="2026" {{ request('year') == '2026' ? 'selected' : '' }}>2026</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
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
                        <td class="fw-bold font-monospace text-success">{{ $exp->voucher_no ?? 'EXP-'.str_pad($exp->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ date('M', mktime(0,0,0,$exp->month,1)) }} {{ $exp->year }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $exp->expense_category }}</span></td>
                        <td>{{ $exp->plant->name ?? 'All Plants' }}</td>
                        <td class="fw-bold text-dark">₹{{ number_format($exp->amount, 2) }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($exp->status) }}">{{ $exp->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('costing.expenses.show', $exp) }}" class="btn btn-sm btn-icon btn-outline-secondary"><i class="bi bi-eye"></i></a>
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