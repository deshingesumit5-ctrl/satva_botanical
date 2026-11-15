@extends('layouts.app')
@section('title', 'BMR: ' . ($batch->batch_no ?? $batch->batch_number))
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="page-title mb-0">Batch Manufacturing Record (BMR)</h2>
                <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $batch->status)) }}">{{ $batch->status }}</span>
            </div>
            <p class="page-subtitle">Master GMP Document for Batch: <strong>{{ $batch->batch_no ?? $batch->batch_number }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('production.bmr.pdf', $batch) }}" class="btn btn-outline-info"><i class="bi bi-printer me-1"></i> Download PDF</a>
            <a href="{{ route('production.batches.show', $batch) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 fw-bold">1. General Batch Information</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3">
                <div class="col-md-3"><label class="text-muted small">Product Name</label><div class="fw-bold">{{ $batch->product->name ?? '-' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Batch Number</label><div class="fw-bold font-monospace text-success">{{ $batch->batch_no ?? $batch->batch_number }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Planned Batch Size</label><div class="fw-bold">{{ $batch->planned_quantity ?? $batch->batch_size }} {{ $batch->uom->symbol ?? 'KG' }}</div></div>
                <div class="col-md-3"><label class="text-muted small">Recipe Specification</label><div class="fw-bold">{{ $batch->recipe->name ?? '-' }} (v{{ $batch->recipeVersion->version_number ?? '1.0' }})</div></div>
            </div>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3 border-bottom"><h6 class="mb-0 fw-bold">2. Stage-Wise Manufacturing Process Log</h6></div>
        <div class="card-sattva-body p-4">
            @foreach($batch->stageExecutions()->orderBy('sequence_no')->get() as $se)
            <div class="border rounded p-3 mb-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold"><span class="badge bg-secondary me-2">Seq {{ $se->sequence_no }}</span> {{ $se->stage->name ?? 'Stage' }}</span>
                    <span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $se->status)) }}">{{ $se->status }}</span>
                </div>
                <div class="row g-2 small text-muted">
                    <div class="col-md-4">Operating Vessel: <strong>{{ $se->equipment->name ?? 'Cleaned' }}</strong></div>
                    <div class="col-md-4">Started: <strong>{{ $se->started_at ? \Carbon\Carbon::parse($se->started_at)->format('d-m-Y H:i') : '-' }}</strong></div>
                    <div class="col-md-4">Completed: <strong>{{ $se->completed_at ? \Carbon\Carbon::parse($se->completed_at)->format('d-m-Y H:i') : '-' }}</strong></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection