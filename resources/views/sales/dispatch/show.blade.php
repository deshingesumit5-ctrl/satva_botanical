@extends('layouts.app')
@section('title', 'Dispatch: ' . $dispatch->dispatch_number)
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $dispatch->dispatch_number }}</h2>
                <span class="badge-status badge-status-{{ strtolower($dispatch->status) }}">{{ $dispatch->status }}</span>
            </div>
            <p class="page-subtitle">
                Customer: <strong>{{ $dispatch->customer->name ?? '-' }}</strong>
                | Dispatch Date: {{ $dispatch->dispatch_date ? \Carbon\Carbon::parse($dispatch->dispatch_date)->format('d-m-Y') : $dispatch->created_at->format('d-m-Y') }}
                @if($dispatch->transport_details || $dispatch->vehicle_number)
                    | Transport: {{ $dispatch->transport_details ?? '-' }} ({{ $dispatch->vehicle_number ?? '-' }})
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(!$dispatch->salesInvoice)
                <a href="{{ route('sales.invoices.create', ['dispatch_id' => $dispatch->id]) }}" class="btn btn-primary-sattva"><i class="bi bi-receipt me-1"></i> Generate Tax Invoice</a>
            @endif
            <a href="{{ route('sales.dispatch.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Dispatched Products & Stock Lots</h6></div>
        <div class="card-sattva-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product Description</th>
                            <th>Allocated Stock Lot</th>
                            <th>Quantity Shipped</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dispatch->items as $idx => $it)
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="fw-bold">{{ $it->product->name ?? '-' }}</td>
                            <td class="font-monospace text-success fw-bold">{{ $it->stockLot->lot_number ?? '-' }}</td>
                            <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($dispatch->remarks)
                <div class="mt-3 text-muted small"><strong>Remarks:</strong> {{ $dispatch->remarks }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
