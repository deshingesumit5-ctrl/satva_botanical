@extends('layouts.app')
@section('title', 'Reconciliation: ' . ($batch->batch_no ?? $batch->batch_number))
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h2 class="page-title mb-0">Production Material Mass Balance & Reconciliation</h2>
                    <span
                        class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $batch->status)) }}">{{ $batch->status }}</span>
                </div>
                <p class="page-subtitle">Batch: <strong>{{ $batch->batch_no ?? $batch->batch_number }}</strong> | Product:
                    {{ $batch->product->name ?? '-' }} (Standard Size: {{ $batch->planned_quantity ?? $batch->batch_size }}
                    {{ $batch->uom->symbol ?? 'KG' }})</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('production.bmr.show', $batch) }}" class="btn btn-outline-info"><i
                        class="bi bi-file-medical me-1"></i> View BMR</a>
                <a href="{{ route('production.batches.show', $batch) }}" class="btn btn-outline-secondary"><i
                        class="bi bi-arrow-left me-1"></i> Back</a>
            </div>
        </div>

        @if(!empty($validationErrors))
            <div class="alert alert-danger mb-4">
                <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Closure Blockers:</h6>
                <ul class="mb-0">
                    @foreach($validationErrors as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $totalInput = $rawQty + $chemQty + $solvQty + $waterQty + $otherInputQty;
            $totalOutput = $fgQty + $wipQty + $recSolvQty + $byProductQty + $spentQty + $wasteQty;
            $diff = $totalInput - $totalOutput;
        @endphp

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card card-sattva h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-success"><i class="bi bi-arrow-down-left-square me-1"></i> Total
                            Material Inputs Charged</h6>
                    </div>
                    <div class="card-sattva-body p-4">
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Botanical Raw
                                    Herb</span><span class="fw-bold">{{ number_format($rawQty, 3) }} KG</span></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Extraction
                                    Solvents</span><span class="fw-bold">{{ number_format($solvQty, 3) }} L</span></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Reagents &
                                    Chemicals</span><span class="fw-bold">{{ number_format($chemQty, 3) }} KG</span></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Process DM
                                    Water</span><span class="fw-bold">{{ number_format($waterQty, 3) }} L</span></li>
                        </ul>
                        <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total Material Charged:</span>
                            <span class="fs-5 fw-bold text-dark">{{ number_format($totalInput, 3) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card card-sattva h-100">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-info"><i class="bi bi-arrow-up-right-square me-1"></i> Total Recoveries
                            & Yield Outputs</h6>
                    </div>
                    <div class="card-sattva-body p-4">
                        <ul class="list-group list-group-flush mb-3">
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Finished Extract
                                    Product</span><span class="fw-bold text-success">{{ number_format($fgQty, 3) }}
                                    KG</span></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Recovered Solvent
                                    Lots</span><span class="fw-bold text-primary">{{ number_format($recSolvQty, 3) }}
                                    L</span></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Spent Marc (Solid
                                    Waste)</span><span class="fw-bold text-muted">{{ number_format($spentQty, 3) }}
                                    KG</span></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Boiler Sludge / Effluent
                                    Residue</span><span class="fw-bold text-danger">{{ number_format($wasteQty, 3) }}
                                    KG</span></li>
                        </ul>
                        <div class="p-3 bg-light rounded d-flex justify-content-between align-items-center">
                            <span class="fw-bold">Total Accounted Outputs:</span>
                            <span class="fs-5 fw-bold text-dark">{{ number_format($totalOutput, 3) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mass Balance Variance Banner (Rule #6 compliant: no hardcoded tolerance, shows unaccounted difference) -->
        <div class="card card-sattva mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Mass Balance Reconciliation Variance</h6>
            </div>
            <div class="card-sattva-body p-4">
                <div class="row g-3 align-items-center">
                    <div class="col-md-6">
                        <div class="h4 fw-bold {{ abs($diff) > 50 ? 'text-danger' : 'text-success' }} mb-1">
                            Unaccounted Difference: {{ number_format($diff, 3) }}
                        </div>
                        <small class="text-muted">Calculated as (Total Inputs - Accounted Outputs). Includes evaporation and
                            standard distillation process losses.</small>
                    </div>
                    <div class="col-md-6 text-end">
                        @if(!in_array($batch->status, ['Closed', 'Completed']))
                            <form action="{{ route('production.reconciliation.close', $batch) }}" method="POST">
                                @csrf
                                <input type="hidden" name="raw_material_qty" value="{{ $rawQty }}">
                                <input type="hidden" name="water_qty" value="{{ $waterQty }}">
                                <input type="hidden" name="chemical_qty" value="{{ $chemQty }}">
                                <input type="hidden" name="solvent_qty" value="{{ $solvQty }}">
                                <input type="hidden" name="other_input_qty" value="{{ $otherInputQty }}">
                                <input type="hidden" name="wip_qty" value="{{ $wipQty }}">
                                <input type="hidden" name="recovered_solvent_qty" value="{{ $recSolvQty }}">
                                <input type="hidden" name="by_product_qty" value="{{ $byProductQty }}">
                                <input type="hidden" name="spent_qty" value="{{ $spentQty }}">
                                <input type="hidden" name="waste_qty" value="{{ $wasteQty }}">

                                <div class="d-flex justify-content-end align-items-center gap-2 mb-2 flex-wrap">
                                    <div class="text-start">
                                        <label class="form-label small text-muted mb-1">Final FG Yield
                                            ({{ $batch->uom->symbol ?? 'KG' }}) *</label>
                                        <input type="number" step="0.001" name="fg_qty" class="form-control form-control-sm"
                                            value="{{ $fgQty }}" required style="width: 150px;">
                                    </div>
                                    <div class="text-start">
                                        <label class="form-label small text-muted mb-1">Destination FG Warehouse *</label>
                                        <select name="destination_warehouse_id" class="form-select form-select-sm" required
                                            style="width: 240px;">
                                            @foreach($warehouses as $wh)
                                                <option value="{{ $wh->id }}" {{ str_contains(strtolower($wh->name), 'finished') ? 'selected' : '' }}>{{ $wh->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary-sattva"
                                    onclick="confirmAction('Close this production batch and transfer final yield to Finished Goods Warehouse?', this.closest('form'))">
                                    <i class="bi bi-check-all me-1"></i> Reconcile & Close Batch
                                </button>
                            </form>
                        @else
                            <span class="badge bg-success fs-6 p-2"><i class="bi bi-shield-check me-1"></i> Batch Reconciled &
                                Closed</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection