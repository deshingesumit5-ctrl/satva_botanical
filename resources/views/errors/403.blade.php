@extends('layouts.app')

@section('title', 'Access Denied - 403')

@section('content')
<div class="container py-5 text-center">
    <div class="card-sattva mx-auto p-5" style="max-width: 500px;">
        <div class="mb-4">
            <span class="rounded-circle bg-danger-subtle text-danger p-3 d-inline-flex" style="font-size: 3rem;">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
        </div>
        <h2 class="font-weight-bold text-dark mb-2">Access Denied (403)</h2>
        <p class="text-muted mb-4">{{ $message ?? 'You do not have permission to view this page or perform this action.' }}</p>
        @if(isset($permissionCode))
            <div class="alert alert-light border small text-muted mb-4">
                Required Permission: <code>{{ $permissionCode }}</code>
            </div>
        @endif
        <div>
            <a href="{{ route('dashboard') }}" class="btn btn-primary-sattva">
                <i class="bi bi-house-door me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
