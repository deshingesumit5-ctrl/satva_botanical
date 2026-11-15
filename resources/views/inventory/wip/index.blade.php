@extends('layouts.app')
@section('title', 'Work In Progress (WIP)')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Work In Progress (WIP) Tracking</h2>
            <p class="page-subtitle">Intermediate herbal decoctions, semi-concentrated liquors, and cake precipitates held on floor.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Batch Number</th>
                        <th>Product</th>
                        <th>Current Process Stage</th>
                        <th>WIP Item Description</th>
                        <th>Quantity Held</th>
                        <th>Stage Entry Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($wipRecords as $idx => $wip)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $wip->productionBatch->batch_number ?? '-' }}</td>
                        <td class="fw-semibold text-dark">{{ $wip->productionBatch->product->name ?? '-' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $wip->stage->name ?? '-' }}</span></td>
                        <td>{{ $wip->intermediate_description ?? 'Semi-finished Extract' }}</td>
                        <td class="fw-bold">{{ $wip->quantity }} {{ $wip->uom->symbol ?? '' }}</td>
                        <td>{{ $wip->created_at->format('d-m-Y H:i') }}</td>
                        <td><span class="badge bg-info-subtle text-info border">In Process</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No active work-in-progress records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection