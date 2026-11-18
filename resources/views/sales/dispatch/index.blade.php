@extends('layouts.app')
@section('title', 'Dispatch Notes')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Delivery Challans & Dispatch Register</h2>
                <p class="page-subtitle">Warehouse picking, finished lot packing, vehicle gate passes, and delivery
                    tracking.</p>
            </div>
            <a href="{{ route('sales.dispatch.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-truck me-1"></i> New Dispatch Note
            </a>
        </div>

        <div class="card card-sattva">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Challan / Dispatch No</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Sales Order</th>
                            <th>Transporter / Vehicle</th>
                            <th>Status</th>
                            <th width="140" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dispatches as $idx => $d)
                            <tr>
                                <td>{{ $dispatches->firstItem() + $idx }}</td>
                                <td class="fw-bold font-monospace text-success">{{ $d->dispatch_number }}</td>
                                <td>{{ $d->dispatch_date ? \Carbon\Carbon::parse($d->dispatch_date)->format('d-m-Y') : $d->created_at->format('d-m-Y') }}
                                </td>
                                <td class="fw-semibold text-dark">{{ $d->customer->name ?? '-' }}</td>
                                <td>
                                    @if($d->salesOrder)
                                        <a href="{{ route('sales.orders.show', $d->salesOrder) }}"
                                            class="badge bg-light text-dark border">{{ $d->salesOrder->so_number }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $d->transport_details ?? '-' }} ({{ $d->vehicle_number ?? '-' }})</td>
                                <td><span class="badge-status badge-status-{{ strtolower($d->status) }}">{{ $d->status }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('sales.dispatch.show', $d) }}"
                                        class="btn btn-sm btn-icon btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                                    @if(!$d->salesInvoice)
                                        <a href="{{ route('sales.invoices.create', ['dispatch_id' => $d->id]) }}"
                                            class="btn btn-sm btn-outline-primary" title="Generate Invoice"><i
                                                class="bi bi-receipt me-1"></i> Invoice</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No dispatch notes recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($dispatches->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $dispatches->firstItem() }} to {{ $dispatches->lastItem() }} of
                        {{ $dispatches->total() }}</span>
                    {{ $dispatches->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection