@extends('layouts.app')
@section('title', 'Sales Orders')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Sales Orders & Client Contracts</h2>
            <p class="page-subtitle">Domestic nutraceutical clients and overseas herbal extract purchase contracts.</p>
        </div>
        <a href="{{ route('sales.orders.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> New Sales Order
        </a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('sales.orders.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search order number, customer..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Confirmed" {{ request('status') === 'Confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="In Progress" {{ request('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="Dispatched" {{ request('status') === 'Dispatched' ? 'selected' : '' }}>Dispatched</option>
                        <option value="Complete" {{ request('status') === 'Complete' ? 'selected' : '' }}>Complete</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Order Number</th>
                        <th>Order Date</th>
                        <th>Customer</th>
                        <th>Delivery Date</th>
                        <th>Total Value</th>
                        <th>Status</th>
                        <th width="160" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $idx => $so)
                    <tr>
                        <td>{{ $orders->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $so->order_number }}</td>
                        <td>{{ $so->created_at->format('d-m-Y') }}</td>
                        <td class="fw-semibold text-dark">{{ $so->customer->name ?? '-' }}</td>
                        <td>{{ $so->delivery_date ? \Carbon\Carbon::parse($so->delivery_date)->format('d-m-Y') : '-' }}</td>
                        <td class="fw-bold">₹{{ number_format($so->total_amount, 2) }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $so->status)) }}">{{ $so->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('sales.orders.show', $so) }}" class="btn btn-sm btn-icon btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                            @if($so->status === 'Confirmed' || $so->status === 'In Progress')
                                <a href="{{ route('sales.dispatch.create', ['order_id' => $so->id]) }}" class="btn btn-sm btn-outline-success me-1" title="Dispatch"><i class="bi bi-truck me-1"></i> Ship</a>
                            @endif
                            <a href="{{ route('sales.orders.edit', $so) }}" class="btn btn-sm btn-icon btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No sales orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }}</span>
            {{ $orders->links() }}
        </div>
        @endif
    </div>
</div>
@endsection