@extends('layouts.auth')

@section('content')
<div class="login-logo-container">
    <img src="{{ asset('images/sattva-logo.png') }}" alt="Sattva Botanicals" class="login-logo">
</div>

{{-- Red alert for general authentication & account errors --}}
@if(session('error'))
    <div class="alert alert-danger py-2 px-3 small rounded-2 mb-3">
        <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
    </div>
@elseif($errors->has('login') && ($errors->first('login') === 'Invalid username/email or password.' || str_contains($errors->first('login'), 'Too many login attempts') || str_contains($errors->first('login'), 'inactive')))
    <div class="alert alert-danger py-2 px-3 small rounded-2 mb-3">
        <i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first('login') }}
    </div>
@endif

@if(session('status') || session('success'))
    <div class="alert alert-success py-2 px-3 small rounded-2 mb-3">
        <i class="bi bi-check-circle me-1"></i> {{ session('status') ?? session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('login.post') }}" id="loginForm">
    @csrf

    <div class="mb-3">
        <label for="login" class="login-form-label">Username or Email</label>
        <input type="text" 
               name="login" 
               id="login" 
               class="login-input @if($errors->has('login') && !in_array($errors->first('login'), ['Invalid username/email or password.']) && !str_contains($errors->first('login'), 'inactive') && !str_contains($errors->first('login'), 'Too many login attempts')) is-invalid @endif" 
               value="{{ old('login') }}" 
               required 
               autofocus 
               autocomplete="username" 
               placeholder="Enter username or email">
        @if($errors->has('login') && !in_array($errors->first('login'), ['Invalid username/email or password.']) && !str_contains($errors->first('login'), 'inactive') && !str_contains($errors->first('login'), 'Too many login attempts'))
            <div class="field-error-text">
                <i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first('login') }}
            </div>
        @endif
    </div>

    <div class="mb-2">
        <label for="password" class="login-form-label">Password</label>
        <div class="password-field-wrapper">
            <input type="password" 
                   name="password" 
                   id="password" 
                   class="login-input @error('password') is-invalid @enderror" 
                   required 
                   value=""
                   autocomplete="new-password" 
                   readonly
                   onfocus="this.removeAttribute('readonly')"
                   placeholder="Enter your password">
            <button class="password-toggle-btn" type="button" onclick="togglePassword('password', 'passwordToggleIcon')" aria-label="Toggle password visibility">
                <i class="bi bi-eye" id="passwordToggleIcon"></i>
            </button>
        </div>
        @error('password')
            <div class="field-error-text">
                <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
            </div>
        @enderror
    </div>

    <div class="mb-3 text-start">
        <a href="javascript:void(0)" class="forgot-password-link" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
    </div>

    <div class="login-form-check">
        <input class="login-checkbox" type="checkbox" name="remember" id="remember" checked>
        <label class="login-checkbox-label" for="remember">
            Remember me
        </label>
    </div>

    <button type="submit" id="signInBtn" class="btn-signin">
        <span id="signInSpinner" class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
        <span id="signInText">Sign in</span>
    </button>
</form>

<script>
    function clearPasswordField() {
        const pwd = document.getElementById('password');
        if (pwd) {
            pwd.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        clearPasswordField();
        setTimeout(clearPasswordField, 300);

        const form = document.getElementById('loginForm');
        if (form) {
            form.addEventListener('submit', function () {
                const btn = document.getElementById('signInBtn');
                const spinner = document.getElementById('signInSpinner');
                const text = document.getElementById('signInText');
                if (btn && form.checkValidity()) {
                    btn.disabled = true;
                    if (spinner) spinner.classList.remove('d-none');
                    if (text) text.textContent = 'Signing in...';
                }
            });
        }
    });

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            clearPasswordField();
        }
    });
</script>
@endsection
