@extends('layouts.app')
@section('title', 'Allocate Materials: ' . ($batch->batch_no ?? $batch->batch_number))
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Multi-Lot Material Allocation</h2>
            <p class="page-subtitle">Batch: <strong>{{ $batch->batch_no ?? $batch->batch_number }}</strong> | Product: {{ $batch->product->name ?? '-' }} (Planned: {{ $batch->planned_quantity }} {{ $batch->uom->symbol ?? 'KG' }})</p>
        </div>
        <a href="{{ route('production.batches.show', $batch) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back to Batch</a>
    </div>

    <div class="card card-sattva">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Allocate Available Lots (FIFO Recommendation)</h6></div>
        <div class="card-sattva-body p-4">
            <form action="{{ route('production.batches.save-allocation', $batch) }}" method="POST">
                @csrf
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Material</th>
                                <th>BOM Required Qty</th>
                                <th>Select Stock Lot</th>
                                <th>Lot Available</th>
                                <th width="180">Allocate Qty *</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batch->requirements as $idx => $req)
                            @php
                                $lots = $availableLots[$req->material_id] ?? collect();
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $req->material->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $req->material->material_code ?? '' }}</small>
                                    <input type="hidden" name="allocations[{{ $idx }}][material_id]" value="{{ $req->material_id }}">
                                </td>
                                <td class="fw-bold">{{ $req->required_qty }} {{ $req->uom->symbol ?? '' }}</td>
                                <td>
                                    <select name="allocations[{{ $idx }}][stock_lot_id]" class="form-select form-select-sm" required>
                                        <option value="">Select Lot</option>
                                        @foreach($lots as $lot)
                                            <option value="{{ $lot->id }}">
                                                {{ $lot->lot_number }} (Avail: {{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>{{ $lots->sum('current_quantity') }} {{ $req->uom->symbol ?? '' }}</td>
                                <td>
                                    <input type="number" step="0.001" name="allocations[{{ $idx }}][allocated_qty]" class="form-control form-control-sm" value="{{ $req->required_qty }}" required>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('production.batches.show', $batch) }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Allocation & Reserve Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection