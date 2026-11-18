@extends('layouts.app')
@section('title', 'Expense Allocation to Batches')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Overhead Expense Allocation Engine</h2>
            <p class="page-subtitle">Distribute approved factory electricity, fuel and labor overheads to closed production batches proportional to run duration.</p>
        </div>
        <a href="{{ route('costing.batch-cost.index') }}" class="btn btn-primary-sattva">
            <i class="bi bi-calculator me-1"></i> View Unit Batch Costs
        </a>
    </div>

    <!-- Unallocated Monthly Expenses Banner -->
    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Approved Overhead Expenses Ready for Allocation</h6></div>
        <div class="card-sattva-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Period</th>
                            <th>Expense Category</th>
                            <th>Total Amount (₹)</th>
                            <th>Vouchers</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unallocatedExpenses as $ue)
                        <tr>
                            <td class="fw-bold">{{ date('F', mktime(0,0,0,$ue->month,1)) }} {{ $ue->year }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $ue->expense_category }}</span></td>
                            <td class="fw-bold text-success">₹{{ number_format($ue->total_amount, 2) }}</td>
                            <td>{{ $ue->voucher_count }} vouchers</td>
                            <td class="text-end">
                                @if(!empty($ue->already_allocated))
                                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-2">Already Allocated</span>
                                @else
                                    <form action="{{ route('costing.allocation.save') }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $ue->month }}">
                                        <input type="hidden" name="year" value="{{ $ue->year }}">
                                        <input type="hidden" name="expense_category" value="{{ $ue->expense_category }}">
                                        <button type="submit" class="btn btn-sm btn-primary-sattva">
                                            <i class="bi bi-distribute-vertical me-1"></i> Allocate to Batches
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-3 text-muted">All approved expenses have been allocated or no expenses found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Completed Allocations -->
    <div class="card card-sattva">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Historical Cost Allocation Runs</h6></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Allocation Period</th>
                        <th>Expense Category</th>
                        <th>Allocated Total (₹)</th>
                        <th>Batches Affected</th>
                        <th>Run By</th>
                        <th>Run Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allocations as $idx => $alloc)
                    <tr>
                        <td>{{ $allocations->firstItem() + $idx }}</td>
                        <td class="fw-bold">{{ date('F', mktime(0,0,0,$alloc->month,1)) }} {{ $alloc->year }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $alloc->expense_category }}</span></td>
                        <td class="fw-bold text-success">₹{{ number_format($alloc->total_expense, 2) }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $alloc->items->count() ?: $alloc->eligible_batch_count }} Batches</span></td>
                        <td>{{ $alloc->allocatedByUser->name ?? 'System' }}</td>
                        <td>{{ $alloc->created_at->format('d-m-Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No historical allocation runs recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($allocations->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $allocations->firstItem() }} to {{ $allocations->lastItem() }} of {{ $allocations->total() }}</span>
            {{ $allocations->links() }}
        </div>
        @endif
    </div>
</div>
@endsection