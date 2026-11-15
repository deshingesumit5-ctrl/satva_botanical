@extends('layouts.app')
@section('title', 'Solvent Recovery Efficiency Report')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Solvent Recovery Efficiency & Loss Report</h2>
            <p class="page-subtitle">Track distillation yields, recovered solvent volumes, reuse percentage and environmental losses.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Recovery Lot #</th>
                        <th>Solvent Material</th>
                        <th>Charged Spent (L)</th>
                        <th>Recovered Pure (L)</th>
                        <th>Recovery %</th>
                        <th>Loss Volume (L)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lots as $idx => $l)
                    @php
                        $charged = (float)($l->initial_quantity ?? 0);
                        $recv = (float)($l->current_quantity ?? 0);
                        $pct = $charged > 0 ? round(($recv / $charged) * 100, 1) : 0;
                        $loss = max(0, $charged - $recv);
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $l->lot_number }}</td>
                        <td class="fw-semibold text-dark">{{ $l->material->name ?? 'Solvent' }}</td>
                        <td>{{ number_format($charged, 2) }} L</td>
                        <td class="fw-bold text-success">{{ number_format($recv, 2) }} L</td>
                        <td><span class="badge {{ $pct >= 85 ? 'bg-success' : 'bg-warning text-dark' }}">{{ $pct }}%</span></td>
                        <td class="text-danger fw-bold">{{ number_format($loss, 2) }} L</td>
                        <td><span class="badge-status badge-status-{{ strtolower($l->status) }}">{{ $l->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No recovery lots recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection