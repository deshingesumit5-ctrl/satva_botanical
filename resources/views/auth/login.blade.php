@extends('layouts.auth')

@section('content')
<form method="POST" action="{{ route('login.post') }}">
    @csrf

    <div class="mb-3">
        <label for="login" class="form-label fw-semibold text-secondary small">Username or Email</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input type="text" name="login" id="login" class="form-control bg-light border-start-0" 
                   value="{{ old('login', 'admin') }}" required autofocus placeholder="admin or admin@sattvabotanical.local">
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label fw-semibold text-secondary small">Password</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control bg-light border-start-0 border-end-0" 
                   required placeholder="Enter your password">
            <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePassword('password', 'passwordToggleIcon')">
                <i class="bi bi-eye" id="passwordToggleIcon"></i>
            </button>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" checked>
            <label class="form-check-label small text-secondary" for="remember">
                Remember me
            </label>
        </div>
    </div>

    <button type="submit" class="btn btn-primary-sattva w-100 py-2 fw-bold shadow">
        Sign In <i class="bi bi-arrow-right ms-1"></i>
    </button>
</form>

<div class="mt-4 pt-3 border-top text-center">
    <div class="badge bg-light text-muted border p-2 small font-monospace">
        Default: <strong>admin</strong> / <strong>Admin@12345</strong>
    </div>
</div>
@endsection
