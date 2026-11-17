@extends('layouts.app')
@section('title', 'Add New User')
@section('content')
<div class="container-fluid px-0" style="max-width: 820px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Create User Account</h2>
            <p class="page-subtitle">Configure login credentials, role and plant permissions.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('admin.users.store') }}" method="POST" autocomplete="off" id="createUserForm" data-anti-autofill="create-user">
                @csrf

                <!-- Two invisible decoy inputs placed off-screen (not display:none) before credentials -->
                <div aria-hidden="true" style="position: absolute; left: -9999px; top: -9999px; width: 1px; height: 1px; overflow: hidden; opacity: 0; pointer-events: none;">
                    <input type="text" name="decoy_username_prevent_autofill" tabindex="-1" autocomplete="username">
                    <input type="password" name="decoy_password_prevent_autofill" tabindex="-1" autocomplete="current-password">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Employee / Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required placeholder="e.g. Ramesh Kumar" autocomplete="off">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Employee Code / ID</label>
                        <input type="text" name="employee_code" value="{{ old('employee_code') }}" class="form-control @error('employee_code') is-invalid @enderror" placeholder="Auto-generated if blank (e.g. USR-0002)" autocomplete="off">
                        @error('employee_code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required placeholder="user@sattvabotanical.local" autocomplete="off">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control @error('mobile') is-invalid @enderror" placeholder="+91 9876543210" autocomplete="off">
                        @error('mobile')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" id="create_user_username" value=""
                            class="form-control anti-autofill-username @error('username') is-invalid @enderror"
                            required placeholder="e.g. ramesh.k"
                            autocomplete="off" readonly onfocus="this.removeAttribute('readonly');">
                        @error('username')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Password</label>
                        <div class="position-relative">
                            <input type="password" name="password" id="create_user_password" value=""
                                class="form-control pe-5 anti-autofill-password @error('password') is-invalid @enderror"
                                required minlength="8" placeholder="Min 8 characters"
                                autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                            <button type="button" class="btn btn-sm border-0 bg-transparent position-absolute top-50 end-0 translate-middle-y me-2 text-muted password-eye-toggle"
                                data-target="create_user_password" tabindex="-1" title="Show/Hide Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Confirm Password</label>
                        <div class="position-relative">
                            <input type="password" name="password_confirmation" id="create_user_password_confirmation" value=""
                                class="form-control pe-5 anti-autofill-password"
                                required minlength="8" placeholder="Confirm password"
                                autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly');">
                            <button type="button" class="btn btn-sm border-0 bg-transparent position-absolute top-50 end-0 translate-middle-y me-2 text-muted password-eye-toggle"
                                data-target="create_user_password_confirmation" tabindex="-1" title="Show/Hide Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Role</label>
                        <select name="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assigned Plant</label>
                        <select name="plant_id" class="form-select">
                            <option value="">All Plants (Super Access)</option>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}" {{ old('plant_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select Department</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" style="max-width: 200px;">
                        <option value="Active" {{ old('status', 'Active') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function clearCreateUserCredentials(root) {
        const scope = root || document;
        scope.querySelectorAll('.anti-autofill-username, .anti-autofill-password').forEach(function (el) {
            el.value = '';
            el.setAttribute('readonly', 'readonly');
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        clearCreateUserCredentials(document);
        setTimeout(function () { clearCreateUserCredentials(document); }, 50);
    });

    window.addEventListener('pageshow', function () {
        clearCreateUserCredentials(document);
    });

    document.addEventListener('show.bs.modal', function (e) {
        clearCreateUserCredentials(e.target);
    });
})();
</script>
@endpush
