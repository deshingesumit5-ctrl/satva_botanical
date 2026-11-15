@extends('layouts.app')
@section('title', 'Process Waste & Residue')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Process Waste & Hazardous Residue Register</h2>
            <p class="page-subtitle">Track spent botanical marc, distillation boiler sludge, ETP effluent, and hazardous waste disposal.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Waste ID</th>
                        <th>Production Batch</th>
                        <th>Waste Category</th>
                        <th>Quantity Generated</th>
                        <th>Disposal Method</th>
                        <th>Disposal Date</th>
                        <th>Authorized Signatory</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wasteRecords as $idx => $w)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace">{{ $w->waste_code ?? 'WST-'.str_pad($w->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-bold text-success">{{ $w->productionBatch->batch_number ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $w->waste_category ?? 'Spent Marc' }}</span></td>
                        <td class="fw-bold">{{ $w->quantity }} {{ $w->uom ?? 'KG' }}</td>
                        <td>{{ $w->disposal_method ?? 'Bio-Composting / Incineration' }}</td>
                        <td>{{ $w->disposed_at ? \Carbon\Carbon::parse($w->disposed_at)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $w->authorizedBy->name ?? 'Plant Officer' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No process waste records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection