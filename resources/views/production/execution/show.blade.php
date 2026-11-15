@extends('layouts.app')
@section('title', 'Execute Batch: ' . ($batch->batch_no ?? $batch->batch_number))
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">Production Execution (BMR Run)</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $batch->status)) }}">{{ $batch->status }}</span>
            </div>
            <p class="page-subtitle">Batch: <strong>{{ $batch->batch_no ?? $batch->batch_number }}</strong> | Product: {{ $batch->product->name ?? '-' }} (Planned: {{ $batch->planned_quantity ?? $batch->batch_size }} {{ $batch->uom->symbol ?? 'KG' }})</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.bmr.show', $batch) }}" class="btn btn-outline-info"><i class="bi bi-file-medical me-1"></i> Live BMR</a>
            <a href="{{ route('production.reconciliation.show', $batch) }}" class="btn btn-outline-warning"><i class="bi bi-calculator me-1"></i> Reconciliation</a>
            <a href="{{ route('production.batches.show', $batch) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Batch Overview</a>
        </div>
    </div>

    <!-- Stage Sequence Pills -->
    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-2">
            <div class="d-flex overflow-auto gap-2 py-1">
                @foreach($stageExecutions as $se)
                    @php
                        $isActive = $activeExecution && $activeExecution->id == $se->id;
                        $isDone = $se->status === 'Completed';
                        $pillClass = $isActive ? 'btn-primary-sattva text-white fw-bold shadow-sm' : ($isDone ? 'btn-light text-success border-success-subtle' : 'btn-light text-muted');
                    @endphp
                    <a href="{{ route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $se->id]) }}" class="btn btn-sm text-nowrap {{ $pillClass }}">
                        @if($isDone) <i class="bi bi-check-circle-fill me-1"></i> @endif
                        Seq {{ $se->sequence_no }}: {{ $se->stage->name ?? 'Stage' }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @if($activeExecution)
    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-secondary me-2">Seq {{ $activeExecution->sequence_no }}</span>
                <span class="fw-bold fs-5 text-dark">{{ $activeExecution->stage->name ?? 'Stage' }}</span>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $activeExecution->status)) }} ms-2">{{ $activeExecution->status }}</span>
            </div>
            <div class="d-flex gap-2">
                @if($activeExecution->status === 'Pending')
                    <form action="{{ route('production.execution.start-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-play-fill me-1"></i> Start Stage</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-sattva-body p-4">
            <form action="{{ route('production.execution.save-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}" method="POST">
                @csrf

                <!-- Section A: Line Clearance & Equipment -->
                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-gear-wide-connected me-1"></i> Section A: Line Clearance & Equipment Log</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label required">Vessel / Equipment</label>
                            <select name="equipment_id" class="form-select" required>
                                <option value="">Select Operating Vessel</option>
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}" {{ $activeExecution->equipment_id == $eq->id ? 'selected' : '' }}>{{ $eq->name }} ({{ $eq->equipment_type }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Line Clearance Performed By</label>
                            <input type="text" name="line_clearance_by" class="form-control" value="{{ $activeExecution->line_clearance_by ?? auth()->user()->name }}" placeholder="Operator name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cleanliness Verification</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_clean" value="1" id="isClean" {{ $activeExecution->line_clearance_done ? 'checked' : '' }}>
                                <label class="form-check-label" for="isClean">Vessel Clean & Free of Prior Product</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section B: Monitored Process Parameters -->
                <div class="mb-4">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-speedometer2 me-1"></i> Section B: Monitored Process Parameters (Temp, Pressure, pH)</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Parameter</th>
                                    <th>Target Spec</th>
                                    <th>Min - Max Range</th>
                                    <th width="200">Logged Value *</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($activeExecution->parameterLogs ?? $activeExecution->parameters ?? []) as $idx => $pLog)
                                <tr>
                                    <td class="fw-bold">{{ $pLog->parameter->name ?? 'Parameter' }}</td>
                                    <td>{{ $pLog->target_value ?? '-' }}</td>
                                    <td>{{ $pLog->min_value ?? 0 }} - {{ $pLog->max_value ?? 0 }}</td>
                                    <td>
                                        <input type="hidden" name="parameters[{{ $idx }}][id]" value="{{ $pLog->id }}">
                                        <input type="text" name="parameters[{{ $idx }}][logged_value]" class="form-control form-control-sm" value="{{ $pLog->logged_value }}" placeholder="Enter reading">
                                    </td>
                                    <td>
                                        @if($pLog->is_in_range)
                                            <span class="badge bg-success">Within Spec</span>
                                        @elseif($pLog->logged_value !== null)
                                            <span class="badge bg-danger">Deviation</span>
                                        @else
                                            <span class="badge bg-secondary">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-2 text-muted">No parameters configured for this stage.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section C: Material Charges & Consumptions -->
                <div class="mb-4">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-box-arrow-down me-1"></i> Section C: Materials & Solvents Charged</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Material Description</th>
                                    <th>Direction</th>
                                    <th>BOM Standard Qty</th>
                                    <th width="200">Actual Charged Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($activeExecution->consumptions ?? []) as $idx => $cons)
                                <tr>
                                    <td class="fw-bold">{{ $cons->material->name ?? '-' }}</td>
                                    <td><span class="badge bg-light text-dark border">Charge (Input)</span></td>
                                    <td>{{ $cons->standard_quantity ?? 0 }} {{ $cons->uom->symbol ?? '' }}</td>
                                    <td>
                                        <input type="hidden" name="materials[{{ $idx }}][id]" value="{{ $cons->id }}">
                                        <input type="number" step="0.001" name="materials[{{ $idx }}][actual_quantity]" class="form-control form-control-sm" value="{{ $cons->actual_quantity }}">
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center py-2 text-muted">No material charges defined for this stage.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section D: Stage Yield & Output Intermediate -->
                <div class="mb-4 p-3 bg-light rounded">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-droplet-half me-1"></i> Section D: Intermediate Yield & Solvent Recoveries</h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Stage Intermediate Output Qty</label>
                            <input type="number" step="0.001" name="output_quantity" class="form-control" value="{{ $activeExecution->output_quantity ?? 0 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Solvent Recovered (Litres)</label>
                            <input type="number" step="0.001" name="solvent_recovered_qty" class="form-control" value="{{ $activeExecution->solvent_recovered_qty ?? 0 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Solid Waste / Spent Herb (KG)</label>
                            <input type="number" step="0.001" name="spent_herb_qty" class="form-control" value="{{ $activeExecution->spent_herb_qty ?? 0 }}">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between pt-3 border-top">
                    <button type="submit" class="btn btn-primary-sattva"><i class="bi bi-save me-1"></i> Save Stage Log</button>
                    @if($activeExecution->status === 'In Progress')
                        <button type="button" class="btn btn-success" onclick="confirmAction('Complete this stage and advance to the next manufacturing stage?', document.getElementById('completeStageForm'))">
                            <i class="bi bi-check-all me-1"></i> Complete Stage & Advance
                        </button>
                    @endif
                </div>
            </form>

            <form id="completeStageForm" action="{{ route('production.execution.complete-stage', ['batch' => $batch->id, 'stageExecution' => $activeExecution->id]) }}" method="POST" class="d-none">
                @csrf
            </form>
        </div>
    </div>
    @endif
</div>
@endsection