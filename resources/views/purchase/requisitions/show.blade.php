@extends('layouts.app')
@section('title', 'Requisition: ' . $requisition->pr_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $requisition->pr_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $requisition->status)) }}">{{ $requisition->status }}</span>
            </div>
            <p class="page-subtitle">Requested by: {{ $requisition->requestedByUser->name ?? 'System' }} on {{ $requisition->created_at->format('d-m-Y') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($requisition->status === 'Draft')
                <form action="{{ route('purchase.requisitions.submit', $requisition) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="button" class="btn btn-warning" onclick="confirmAction('Submit this requisition for manager approval?', this.closest('form'))">Submit for Approval</button>
                </form>
            @endif

            @if($requisition->status === 'Submitted')
                <form action="{{ route('purchase.requisitions.approve', $requisition) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="button" class="btn btn-success" onclick="confirmAction('Approve this purchase requisition?', this.closest('form'))">Approve</button>
                </form>
                <form action="{{ route('purchase.requisitions.reject', $requisition) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="button" class="btn btn-danger" onclick="confirmAction('Reject this purchase requisition?', this.closest('form'))">Reject</button>
                </form>
            @endif

            @if($requisition->status === 'Approved')
                <a href="{{ route('purchase.orders.create', ['pr_id' => $requisition->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-cart-plus me-1"></i> Convert to Purchase Order</a>
            @endif

            <a href="{{ route('purchase.requisitions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Requisition Details</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><label class="text-muted small">Plant</label><div class="fw-semibold">{{ $requisition->plant->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Department</label><div class="fw-semibold">{{ $requisition->department->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Required By Date</label><div class="fw-semibold">{{ $requisition->required_by_date ? \Carbon\Carbon::parse($requisition->required_by_date)->format('d-m-Y') : '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Remarks</label><div class="fw-semibold">{{ $requisition->remarks ?? '-' }}</div></div>
            </div>

            <h6 class="fw-bold mb-3">Requested Materials</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Material Code</th>
                            <th>Material Name</th>
                            <th>Quantity</th>
                            <th>UOM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requisition->items as $idx => $item)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-bold text-success">{{ $item->material->material_code ?? '-' }}</td>
                            <td class="fw-semibold text-dark">{{ $item->material->name ?? '-' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->uom->symbol ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection