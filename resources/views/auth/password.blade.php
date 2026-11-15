@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<x-page-header title="Change Password" subtitle="Update your account security password." />

<div class="row">
    <div class="col-lg-6">
        <div class="card-sattva">
            <div class="card-sattva-header">
                <h5 class="fw-bold m-0 text-dark">Password Settings</h5>
            </div>
            <div class="card-sattva-body">
                <form id="passwordForm" method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Current Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="current_password" id="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                            <button class="btn btn-light border border-start-0" type="button" onclick="togglePassword('current_password', 'currentPassToggle')">
                                <i class="bi bi-eye" id="currentPassToggle"></i>
                            </button>
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password" id="new_password" class="form-control @error('password') is-invalid @enderror" required>
                            <button class="btn btn-light border border-start-0" type="button" onclick="togglePassword('new_password', 'newPassToggle')">
                                <i class="bi bi-eye" id="newPassToggle"></i>
                            </button>
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-text small text-muted">Minimum 8 characters with letters, numbers, and symbols recommended.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-muted">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                            <button class="btn btn-light border border-start-0" type="button" onclick="togglePassword('password_confirmation', 'confirmPassToggle')">
                                <i class="bi bi-eye" id="confirmPassToggle"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary-sattva px-4" onclick="confirmEdit('passwordForm', 'Password')">
                        <i class="bi bi-shield-check me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
