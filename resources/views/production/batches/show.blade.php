@extends('layouts.app')
@section('title', 'Batch: ' . ($batch->batch_number ?? $batch->batch_no))
@section('content')
@php
    $currentExec = $batch->stageExecutions->firstWhere('status', 'In Progress')
        ?? $batch->stageExecutions->firstWhere('status', 'QC Pending')
        ?? $batch->stageExecutions->firstWhere('status', 'Rework Required')
        ?? $batch->stageExecutions->last();
@endphp
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">{{ $batch->batch_number ?? $batch->batch_no }}</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $batch->status)) }}">{{ $batch->status }}</span>
            </div>
            <p class="page-subtitle">Product: <strong>{{ $batch->product->name ?? '-' }}</strong> | Recipe: {{ $batch->recipe->name ?? '-' }} (v{{ $batch->recipeVersion->version_number ?? '1.0' }})</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.execution.show', $batch) }}" class="btn btn-primary-sattva"><i class="bi bi-play-circle me-1"></i> Stage Execution</a>
            <a href="{{ route('production.batches.allocate', $batch) }}" class="btn btn-outline-info"><i class="bi bi-box-seam me-1"></i> Multi-Lot Allocation</a>
            <a href="{{ route('production.bmr.show', $batch) }}" class="btn btn-outline-secondary"><i class="bi bi-file-medical me-1"></i> BMR</a>
            <a href="{{ route('production.reconciliation.show', $batch) }}" class="btn btn-outline-warning"><i class="bi bi-calculator me-1"></i> Reconciliation</a>
            <a href="{{ route('production.batches.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Batch Parameters & Run History</h6></div>
                <div class="card-sattva-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3"><label class="text-muted small">Planned Output</label><div class="fw-bold">{{ $batch->planned_qty ?? $batch->planned_quantity ?? $batch->batch_size }} {{ $batch->uom->symbol ?? 'KG' }}</div></div>
                        <div class="col-md-3"><label class="text-muted small">Actual Produced</label><div class="fw-bold text-success">{{ $batch->actual_qty ?? $batch->actual_quantity ?? 0 }} {{ $batch->uom->symbol ?? 'KG' }}</div></div>
                        <div class="col-md-3"><label class="text-muted small">Current Stage</label><div class="fw-bold">{{ $currentExec?->stage_name ?? $batch->currentStage?->name ?? '-' }}</div></div>
                        <div class="col-md-3"><label class="text-muted small">Shift Supervisor</label><div class="fw-bold">{{ $batch->supervisor->name ?? $batch->supervisor_name ?? 'Assigned' }}</div></div>
                    </div>

                    <h6 class="fw-bold mb-3">Stage Execution Progress</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="60">Seq</th>
                                    <th>Stage Name</th>
                                    <th>Status</th>
                                    <th>QC Request</th>
                                    <th>Started At</th>
                                    <th>Completed At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($batch->stageExecutions()->with(['stage', 'qcRequest'])->orderBy('sequence_no')->orderBy('execution_number')->get() as $se)
                                <tr>
                                    <td class="text-center fw-bold">{{ $se->sequence_no }}</td>
                                    <td class="fw-semibold text-dark">
                                        <a href="{{ route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $se->id]) }}" class="text-decoration-none text-dark">
                                            {{ $se->stage_name ?? $se->stage?->name ?? '-' }}
                                        </a>
                                    </td>
                                    <td><x-status-badge :status="$se->status" /></td>
                                    <td>
                                        @if($se->qcRequest)
                                            <a href="{{ route('qc.show', $se->qcRequest) }}" class="font-monospace fw-bold text-decoration-none text-success">
                                                {{ $se->qcRequest->qc_request_number }}
                                            </a>
                                            <div class="mt-1"><x-status-badge :status="$se->qcRequest->status" /></div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>{{ $se->start_time ? \Carbon\Carbon::parse($se->start_time)->format('d-m-Y H:i') : '-' }}</td>
                                    <td>{{ $se->completed_at ? \Carbon\Carbon::parse($se->completed_at)->format('d-m-Y H:i') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-sattva mb-4">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Batch Actions</h6></div>
                <div class="card-sattva-body p-4">
                    @if($batch->status === 'In Progress')
                        <form action="{{ route('production.batches.hold', $batch) }}" method="POST" class="mb-2">
                            @csrf
                            <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Hold reason..." required>
                            <button type="submit" class="btn btn-warning w-100"><i class="bi bi-pause-circle me-1"></i> Put Batch On Hold</button>
                        </form>
                    @elseif($batch->status === 'On Hold')
                        <form action="{{ route('production.batches.resume', $batch) }}" method="POST" class="mb-2">
                            @csrf
                            <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Resume remarks..." required>
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-play-circle me-1"></i> Resume Batch Run</button>
                        </form>
                    @endif

                    @if($batch->status === 'Draft')
                        <form action="{{ route('production.batches.start', $batch) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary-sattva w-100"><i class="bi bi-play-fill me-1"></i> Start Batch Execution</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
