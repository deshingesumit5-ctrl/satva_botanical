@extends('layouts.app')
@section('title', 'Rework Details')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Rework Protocol: {{ $rework->rework_code ?? 'RWK-'.$rework->id }}</h2>
            <p class="page-subtitle">Parent Batch: <strong>{{ $rework->parentBatch->batch_no ?? '-' }}</strong></p>
        </div>
        <a href="{{ route('production.rework.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Investigation & Remediation</h6></div>
        <div class="card-sattva-body p-4">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="text-muted small">Root Cause Analysis</label><div class="fw-semibold">{{ $rework->reason }}</div></div>
                <div class="col-md-6"><label class="text-muted small">Remediation Procedure</label><div class="fw-semibold">{{ $rework->instructions ?? 'Re-filter through micron filter and crystallize.' }}</div></div>
            </div>
        </div>
    </div>
</div>
@endsection