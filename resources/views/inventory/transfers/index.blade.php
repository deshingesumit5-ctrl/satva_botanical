@extends('layouts.app')
@section('title', 'Stock Transfers')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Internal Stock Transfers</h2>
            <p class="page-subtitle">Movement of inventory lots between plant warehouses, processing sections, and solvent tank farms.</p>
        </div>
        <a href="{{ route('inventory.transfers.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-arrow-left-right me-1"></i> New Transfer
        </a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('inventory.transfers.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search transfer number, remarks..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Transfer Number</th>
                        <th>Date</th>
                        <th>Source Warehouse</th>
                        <th>Destination Warehouse</th>
                        <th>Items Count</th>
                        <th>Status</th>
                        <th width="100" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $idx => $t)
                    <tr>
                        <td>{{ $transfers->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $t->transfer_number }}</td>
                        <td>{{ $t->created_at->format('d-m-Y H:i') }}</td>
                        <td>{{ $t->fromWarehouse->name ?? '-' }}</td>
                        <td>{{ $t->toWarehouse->name ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $t->items->count() }} items</span></td>
                        <td><span class="badge-status badge-status-{{ strtolower($t->status) }}">{{ $t->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('inventory.transfers.show', $t) }}" class="btn btn-sm btn-icon btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No stock transfers recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transfers->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $transfers->firstItem() }} to {{ $transfers->lastItem() }} of {{ $transfers->total() }}</span>
            {{ $transfers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection