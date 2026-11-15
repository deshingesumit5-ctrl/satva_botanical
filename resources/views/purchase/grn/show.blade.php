@extends('layouts.app')
@section('title', 'GRN: ' . $grn->grn_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $grn->grn_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower($grn->status) }}">{{ $grn->status }}</span>
            </div>
            <p class="page-subtitle">Supplier: {{ $grn->supplier->name ?? 'None' }} | Inward Date: {{ $grn->created_at->format('d-m-Y H:i') }}</p>
        </div>
        <div class="d-flex gap-2">
            @if($grn->purchaseOrder)
                <a href="{{ route('purchase.orders.show', $grn->purchaseOrder) }}" class="btn btn-outline-info"><i class="bi bi-link me-1"></i> View PO</a>
            @endif
            <a href="{{ route('purchase.grn.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Inward Material Line Items</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Vendor Lot #</th>
                                    <th>Received Qty</th>
                                    <th>Accepted Qty</th>
                                    <th>Rejected Qty</th>
                                    <th>Assigned Stock Lot</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grn->items as $idx => $it)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $it->material->name ?? '-' }}</div>
                                        <small class="text-muted">{{ $it->material->material_code ?? '' }}</small>
                                    </td>
                                    <td><code>{{ $it->vendor_lot_number ?? '-' }}</code></td>
                                    <td>{{ $it->received_quantity }} {{ $it->uom->symbol ?? '' }}</td>
                                    <td class="text-success fw-bold">{{ $it->accepted_quantity ?? 0 }}</td>
                                    <td class="text-danger fw-bold">{{ $it->rejected_quantity ?? 0 }}</td>
                                    <td>
                                        @if($it->stock_lot_id)
                                            <span class="badge bg-light text-success border font-monospace">LOT-{{ $it->stock_lot_id }}</span>
                                        @else
                                            <span class="text-muted small">Quarantine</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Gate & Logistics Summary</h6></div>
                <div class="card-sattva-body p-4">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Invoice / DC:</span>
                            <span class="fw-semibold">{{ $grn->invoice_number ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Vehicle No:</span>
                            <span class="fw-semibold"><code>{{ $grn->vehicle_number ?? '-' }}</code></span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Gate Pass:</span>
                            <span class="fw-semibold">{{ $grn->gate_pass_number ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Plant:</span>
                            <span class="fw-semibold">{{ $grn->plant->name ?? '-' }}</span>
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span class="text-muted">Warehouse:</span>
                            <span class="fw-semibold">{{ $grn->warehouse->name ?? '-' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection