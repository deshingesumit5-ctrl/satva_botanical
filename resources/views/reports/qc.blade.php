@extends('layouts.app')
@section('title', 'QC Quality Trends Report')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">QC Inspection & Release Compliance Report</h2>
            <p class="page-subtitle">Track pass/reject ratios, out-of-specification occurrences, and laboratory test results.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>QC Request #</th>
                        <th>Sample Type</th>
                        <th>Item Tested</th>
                        <th>Batch / Lot No</th>
                        <th>Decision Date</th>
                        <th>Approved By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $idx => $r)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $r->qc_request_number }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $r->sample_type }}</span></td>
                        <td class="fw-semibold text-dark">{{ $r->material->name ?? $r->batch->product->name ?? '-' }}</td>
                        <td>{{ $r->batch->batch_no ?? $r->stockLot->lot_number ?? '-' }}</td>
                        <td>{{ $r->approved_at ? \Carbon\Carbon::parse($r->approved_at)->format('d-m-Y') : '-' }}</td>
                        <td>{{ $r->approvedByUser->name ?? '-' }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($r->status) }}">{{ $r->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No QC records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection