@extends('layouts.app')
@section('title', 'Audit Trail')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Security & Compliance Audit Trail</h2>
            <p class="page-subtitle">Immutable log of user actions, status changes, approvals, records modified and IP addresses.</p>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('admin.audit-trail.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search record ID, remarks...">
                </div>
                <div class="col-md-2">
                    <select name="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="module" class="form-select">
                        <option value="">All Modules</option>
                        @foreach($modules as $m)
                            <option value="{{ $m }}" {{ request('module') == $m ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        @foreach($actions as $a)
                            <option value="{{ $a }}" {{ request('action') == $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('admin.audit-trail.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead class="table-light">
                    <tr>
                        <th width="150">Timestamp</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Record ID</th>
                        <th>Description / Remarks</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at->format('d-m-Y H:i:s') }}</td>
                        <td class="fw-semibold text-dark">{{ $log->user->name ?? 'System' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ strtoupper($log->module) }}</span></td>
                        <td>
                            @php
                                $badgeClass = match(strtolower($log->action)) {
                                    'create' => 'bg-success',
                                    'update' => 'bg-primary',
                                    'delete' => 'bg-danger',
                                    'approve' => 'bg-success',
                                    'reject' => 'bg-danger',
                                    'login' => 'bg-info',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ strtoupper($log->action) }}</span>
                        </td>
                        <td class="fw-bold">{{ $log->record_id ?? '-' }}</td>
                        <td class="text-break">{{ $log->remarks ?? '-' }}</td>
                        <td class="text-muted">{{ $log->ip_address ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No audit records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }}</span>
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection