@extends('layouts.app')
@section('title', 'Add New User')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Create User Account</h2>
            <p class="page-subtitle">Configure login credentials, role and plant permissions.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Employee / Full Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Ramesh Kumar">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Employee Code / ID</label>
                        <input type="text" name="employee_code" class="form-control" placeholder="e.g. EMP-101">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Email Address</label>
                        <input type="email" name="email" class="form-control" required placeholder="user@sattvabotanical.local">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" placeholder="+91 9876543210">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Username</label>
                        <input type="text" name="username" class="form-control" required placeholder="e.g. ramesh.k">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Password</label>
                        <input type="password" name="password" class="form-control" required placeholder="At least 6 characters">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Role</label>
                        <select name="role_id" class="form-select" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $r)
                                <option value="{{ $r->id }}">{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Assigned Plant</label>
                        <select name="plant_id" class="form-select">
                            <option value="">All Plants (Super Access)</option>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select Department</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" style="max-width: 200px;">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
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