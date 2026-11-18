@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<x-page-header title="My Profile" subtitle="Manage your account profile and personal information." />

<div class="row">
    <div class="col-lg-8">
        <div class="card-sattva">
            <div class="card-sattva-header">
                <h5 class="fw-bold m-0 text-dark">Profile Details</h5>
            </div>
            <div class="card-sattva-body">
                <form id="profileForm" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">User ID</label>
                            <input type="text" class="form-control" value="{{ $user->user_id }}" readonly disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Role</label>
                            <input type="text" class="form-control" value="{{ $user->role->role_name ?? 'N/A' }}" readonly disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Username</label>
                            <input type="text" class="form-control" value="{{ $user->username }}" readonly disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $user->mobile) }}" placeholder="9876543210" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" title="Mobile number must be exactly 10 digits">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Department</label>
                            <input type="text" class="form-control" value="{{ $user->department->name ?? 'N/A' }}" readonly disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Plant</label>
                            <input type="text" class="form-control" value="{{ $user->plant->name ?? 'N/A' }}" readonly disabled>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="button" class="btn btn-primary-sattva px-4" onclick="confirmEdit('profileForm', '{{ $user->name }}')">
                                <i class="bi bi-check-lg me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-sattva text-center p-4">
            <div class="avatar-box mx-auto mb-3" style="width: 90px; height: 90px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #64748b;">
                <i class="bi bi-person-circle"></i>
            </div>
            <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
            <div class="badge bg-success-subtle text-success mb-2">{{ $user->role->role_name ?? 'User' }}</div>
            <p class="text-muted small">{{ $user->email }}</p>
            <hr>
            <div class="text-start small text-muted">
                <div class="mb-2"><i class="bi bi-clock me-2"></i> Member since: {{ $user->created_at->format('d M Y') }}</div>
                <div><i class="bi bi-shield-check me-2"></i> Account Status: <x-status-badge :status="$user->status" /></div>
            </div>
        </div>
    </div>
</div>
@endsection
