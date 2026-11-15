@extends('layouts.app')
@section('title', 'Transfer: ' . $transfer->transfer_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $transfer->transfer_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower($transfer->status) }}">{{ $transfer->status }}</span>
            </div>
            <p class="page-subtitle">Transferred on: {{ $transfer->created_at->format('d-m-Y H:i') }} | User: {{ $transfer->user->name ?? 'System' }}</p>
        </div>
        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Transfer Routing Details</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-md-4"><label class="text-muted small">From Warehouse</label><div class="fw-bold">{{ $transfer->fromWarehouse->name ?? '-' }}</div></div>
                <div class="col-md-4"><label class="text-muted small">To Warehouse</label><div class="fw-bold text-success">{{ $transfer->toWarehouse->name ?? '-' }}</div></div>
                <div class="col-md-4"><label class="text-muted small">Remarks</label><div>{{ $transfer->remarks ?? '-' }}</div></div>
            </div>

            <h6 class="fw-bold mb-3">Transferred Items</h6>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Lot Number</th>
                            <th>Item Description</th>
                            <th>Transferred Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transfer->items as $idx => $it)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-bold font-monospace">{{ $it->stockLot->lot_number ?? '-' }}</td>
                            <td>{{ $it->stockLot->material->name ?? $it->stockLot->product->name ?? '-' }}</td>
                            <td class="fw-bold">{{ $it->quantity }} {{ $it->stockLot->uom->symbol ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection