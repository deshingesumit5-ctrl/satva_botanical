@extends('layouts.app')
@section('title', 'Batch Rework & Reprocessing')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Batch Rework & Reprocessing Register</h2>
            <p class="page-subtitle">Manage out-of-spec batches sent for re-extraction, filtration, or crystallization rework.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Rework Code</th>
                        <th>Parent Batch</th>
                        <th>Rework Reason</th>
                        <th>Assigned Rework Stage</th>
                        <th>Authorized By</th>
                        <th>Status</th>
                        <th width="100" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reworks as $idx => $rw)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-danger">{{ $rw->rework_code ?? 'RWK-'.str_pad($rw->id, 5, '0', STR_PAD_LEFT) }}</td>
                        <td class="fw-bold text-success">{{ $rw->parentBatch->batch_no ?? '-' }}</td>
                        <td>{{ $rw->reason }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $rw->targetStage->name ?? 'Re-crystallization' }}</span></td>
                        <td>{{ $rw->authorizedBy->name ?? 'QA Officer' }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower($rw->status) }}">{{ $rw->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('production.rework.show', $rw) }}" class="btn btn-sm btn-icon btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No batches currently under rework.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection