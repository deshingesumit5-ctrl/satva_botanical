@extends('layouts.app')
@section('title', 'Goods Receipt Notes (GRN)')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Goods Receipt Notes (Inward GRN)</h2>
            <p class="page-subtitle">Track raw material gate inward, weighbridge verification, quarantine and QA sampling.</p>
        </div>
        <a href="{{ route('purchase.grn.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-box-arrow-in-down me-1"></i> New Direct GRN
        </a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('purchase.grn.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search GRN number, invoice, vehicle, supplier..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Quarantine" {{ request('status') === 'Quarantine' ? 'selected' : '' }}>Quarantine (Under QC)</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved</option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('purchase.grn.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>GRN Number</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>PO Ref</th>
                        <th>Invoice / Challan</th>
                        <th>Vehicle No</th>
                        <th>Status</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grns as $idx => $grn)
                    <tr>
                        <td>{{ $grns->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $grn->grn_number }}</td>
                        <td>{{ $grn->created_at->format('d-m-Y') }}</td>
                        <td class="fw-semibold text-dark">{{ $grn->supplier->name ?? '-' }}</td>
                        <td>
                            @if($grn->purchaseOrder)
                                <a href="{{ route('purchase.orders.show', $grn->purchaseOrder) }}" class="badge bg-light text-dark border">{{ $grn->purchaseOrder->po_number }}</a>
                            @else
                                <span class="text-muted small">Direct</span>
                            @endif
                        </td>
                        <td>{{ $grn->invoice_number ?? '-' }}</td>
                        <td><code>{{ $grn->vehicle_number ?? '-' }}</code></td>
                        <td><span class="badge-status badge-status-{{ strtolower($grn->status) }}">{{ $grn->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('purchase.grn.show', $grn) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="View GRN"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No GRN records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($grns->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $grns->firstItem() }} to {{ $grns->lastItem() }} of {{ $grns->total() }}</span>
            {{ $grns->links() }}
        </div>
        @endif
    </div>
</div>
@endsection