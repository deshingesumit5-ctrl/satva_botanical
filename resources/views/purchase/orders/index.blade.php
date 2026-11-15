@extends('layouts.app')
@section('title', 'Purchase Orders')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Purchase Orders (PO)</h2>
            <p class="page-subtitle">Commercial orders issued to approved botanical collectors and chemical distributors.</p>
        </div>
        <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Create Purchase Order
        </a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('purchase.orders.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search PO number, supplier..."></div>
                <div class="col-md-3">
                    <select name="supplier_id" class="form-select">
                        <option value="">All Suppliers</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Partially Received" {{ request('status') === 'Partially Received' ? 'selected' : '' }}>Partially Received</option>
                        <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('purchase.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>PO Number</th>
                        <th>PO Date</th>
                        <th>Supplier</th>
                        <th>Delivery Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th width="180" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $idx => $po)
                    <tr>
                        <td>{{ $orders->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $po->po_number }}</td>
                        <td>{{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('d-m-Y') : $po->created_at->format('d-m-Y') }}</td>
                        <td class="fw-semibold text-dark">{{ $po->supplier->name ?? '-' }}</td>
                        <td>{{ $po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date)->format('d-m-Y') : '-' }}</td>
                        <td class="fw-bold">₹{{ number_format($po->total_amount, 2) }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $po->status)) }}">{{ $po->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('purchase.orders.show', $po) }}" class="btn btn-sm btn-icon btn-outline-secondary me-1" title="View"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('purchase.orders.print', $po) }}" class="btn btn-sm btn-icon btn-outline-info me-1" title="PDF Print"><i class="bi bi-printer"></i></a>
                            @if($po->status === 'Approved' || $po->status === 'Partially Received')
                            <a href="{{ route('purchase.grn.from-po', $po) }}" class="btn btn-sm btn-outline-success me-1" title="Inward GRN"><i class="bi bi-box-arrow-in-down me-1"></i> GRN</a>
                            @endif
                            @if($po->status === 'Draft')
                            <a href="{{ route('purchase.orders.edit', $po) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No purchase orders found.</td></tr>
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