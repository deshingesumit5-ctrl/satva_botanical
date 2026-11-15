@extends('layouts.app')
@section('title', 'Solvent Recovery Management')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Solvent Recovery & Distillation Tracking</h2>
            <p class="page-subtitle">Track spent solvent collection, distillation recovery efficiency %, purity grade and reusable solvent lots.</p>
        </div>
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
                        <th>Charged Spent (L)</th>
                        <th>Distilled Recovered (L)</th>
                        <th>Recovery Efficiency %</th>
                        <th>Purity Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recoveryLots as $idx => $r)
                    @php
                        $eff = ($r->initial_quantity > 0) ? round(($r->current_quantity / $r->initial_quantity) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $r->lot_number }}</td>
                        <td>{{ $r->source_batch_number ?? '-' }}</td>
                        <td class="fw-semibold text-dark">{{ $r->material->name ?? 'Solvent' }}</td>
                        <td>{{ $r->initial_quantity }} L</td>
                        <td class="fw-bold text-success">{{ $r->current_quantity }} L</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ min(100, $eff) }}%"></div>
                                </div>
                                <span class="fw-bold small">{{ $eff }}%</span>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $r->purity_grade ?? 'Standard Reusable' }}</span></td>
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
@endsection