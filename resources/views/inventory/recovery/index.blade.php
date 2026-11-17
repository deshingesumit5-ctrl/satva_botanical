@extends('layouts.app')
@section('title', 'Solvent Recovery Management')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Solvent Recovery & Distillation Tracking</h2>
            <p class="page-subtitle">Track spent solvent collection, distillation recovery efficiency %, purity grade and reusable solvent lots.</p>
        </div>
        @if(auth()->user() && auth()->user()->hasPermission('inventory.recovery.add'))
            <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addRecoveryModal">
                <i class="bi bi-plus-lg me-1"></i> Record Recovered Solvent
            </button>
        @endif
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Recovery Lot #</th>
                        <th>Source Batch</th>
                        <th>Solvent Material</th>
                        <th>Generated (L)</th>
                        <th>Available / Remaining (L)</th>
                        <th>Recovery Efficiency %</th>
                        <th>Warehouse</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recoveryLots as $idx => $r)
                    @php
                        $genQty = $r->generated_qty ?? $r->initial_quantity ?? 0;
                        $remQty = $r->remaining_qty ?? $r->available_qty ?? $r->current_quantity ?? 0;
                        $eff = ($genQty > 0) ? round(($remQty / $genQty) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $r->recovery_lot_number ?? $r->lot_number }}</td>
                        <td>{{ $r->sourceBatch->batch_number ?? $r->source_batch_number ?? '-' }}</td>
                        <td class="fw-semibold text-dark">{{ $r->material->name ?? 'Solvent' }}</td>
                        <td>{{ $genQty }} L</td>
                        <td class="fw-bold text-success">{{ $remQty }} L</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ min(100, $eff) }}%"></div>
                                </div>
                                <span class="fw-bold small">{{ $eff }}%</span>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $r->warehouse->name ?? 'Recovery Store' }}</span></td>
                        <td><span class="badge-status badge-status-{{ strtolower($r->status) }}">{{ $r->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No solvent recovery lots recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<x-form-modal id="addRecoveryModal" title="Record Recovered Solvent Lot" size="standard" :action="route('inventory.recovery.store')" method="POST" :hasForm="true" submitText="Save">
    <div class="mb-3">
        <label class="form-label">Recovery Lot Number</label>
        <input type="text" name="recovery_lot_number" class="form-control" placeholder="Auto-generated if blank (e.g. REC-2026-001)">
    </div>
    <div class="row g-2 mb-3">
        <div class="col-6">
            <label class="form-label required">Source Batch</label>
            <select name="source_batch_id" class="form-select" required>
                <option value="">Select Batch</option>
                @foreach($activeBatches ?? [] as $b)
                    <option value="{{ $b->id }}">{{ $b->batch_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <label class="form-label required">Solvent Material</label>
            <select name="material_id" class="form-select" required>
                <option value="">Select Solvent</option>
                @foreach($materials ?? [] as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row g-2 mb-2">
        <div class="col-4">
            <label class="form-label required">Recovered Qty (L)</label>
            <input type="number" step="0.001" name="generated_qty" class="form-control" placeholder="e.g. 400" required>
        </div>
        <div class="col-4">
            <label class="form-label required">Warehouse</label>
            <select name="warehouse_id" class="form-select" required>
                @foreach($warehouses ?? [] as $wh)
                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4">
            <label class="form-label required">Recovery Date</label>
            <input type="date" name="recovery_date" class="form-control" value="{{ date('Y-m-d') }}" required>
        </div>
    </div>
</x-form-modal>
@endsection
