@extends('layouts.app')
@section('title', 'Allocate Materials: ' . ($batch->batch_no ?? $batch->batch_number))
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Multi-Lot Material Allocation</h2>
            <p class="page-subtitle">Batch: <strong>{{ $batch->batch_number ?? $batch->batch_no }}</strong> | Product: {{ $batch->product->name ?? '-' }} (Planned: {{ $batch->planned_qty ?? $batch->planned_quantity }} {{ $batch->uom->symbol ?? $batch->uom->uom_code ?? 'KG' }})</p>
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
                                <th>Stage</th>
                                <th>BOM Required Qty</th>
                                <th>Already Allocated</th>
                                <th>Select Stock Lot</th>
                                <th>Lot Available</th>
                                <th width="180">Allocate Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batch->materialRequirements as $idx => $req)
                            @php
                                $lots = $availableLots[$req->material_id] ?? collect();
                                $plannedQty = (float) ($req->planned_qty ?? $req->required_qty ?? 0);
                                $alreadyAllocated = (float) ($req->allocated_qty ?? 0);
                                $pendingQty = max(0, $plannedQty - $alreadyAllocated);
                                $uomLabel = $req->uom->symbol ?? $req->uom->uom_code ?? $req->material->baseUom->symbol ?? '';
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $req->material->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $req->material->material_code ?? '' }}</small>
                                    <input type="hidden" name="allocations[{{ $idx }}][requirement_id]" value="{{ $req->id }}">
                                    <input type="hidden" name="allocations[{{ $idx }}][material_id]" value="{{ $req->material_id }}">
                                </td>
                                <td>{{ $req->recipeStage->stage->name ?? $req->recipeStage->stage->stage_name ?? '-' }}</td>
                                <td class="fw-bold">{{ number_format($plannedQty, 3) }} {{ $uomLabel }}</td>
                                <td>{{ number_format($alreadyAllocated, 3) }} {{ $uomLabel }}</td>
                                <td>
                                    <select name="allocations[{{ $idx }}][stock_lot_id]" class="form-select form-select-sm">
                                        <option value="">Select Lot (Optional)</option>
                                        @foreach($lots as $lot)
                                            <option value="{{ $lot->id }}">
                                                {{ $lot->lot_number }} (Avail: {{ $lot->available_qty }} {{ $lot->material->baseUom->symbol ?? $uomLabel }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>{{ number_format($lots->sum('available_qty'), 3) }} {{ $uomLabel }}</td>
                                <td>
                                    <input type="number" step="0.001" min="0" name="allocations[{{ $idx }}][allocated_qty]" class="form-control form-control-sm" value="{{ $pendingQty }}">
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No material requirements found for this batch's recipe.</td>
                            </tr>
                            @endforelse
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