@extends('layouts.app')
@section('title', 'Production Batches')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Production Batches (BMR Runs)</h2>
            <p class="page-subtitle">Batch manufacturing runs, material allocations, execution tracking, and BMR sign-off.</p>
        </div>
        <a href="{{ route('production.batches.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Issue New Batch
        </a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('production.batches.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search batch number, product..."></div>
                <div class="col-md-3">
                    <select name="product_id" class="form-select">
                        <option value="">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                        <option value="Allocated" {{ request('status') === 'Allocated' ? 'selected' : '' }}>Allocated</option>
                        <option value="In Progress" {{ request('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="QC Pending" {{ request('status') === 'QC Pending' ? 'selected' : '' }}>QC Pending</option>
                        <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="On Hold" {{ request('status') === 'On Hold' ? 'selected' : '' }}>On Hold</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('production.batches.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Batch Number</th>
                        <th>Product Name</th>
                        <th>Batch Size</th>
                        <th>Recipe Ver</th>
                        <th>Start Date</th>
                        <th>Status</th>
                        <th width="200" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $idx => $b)
                    <tr>
                        <td>{{ $batches->firstItem() + $idx }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $b->batch_no ?? $b->batch_number }}</td>
                        <td class="fw-semibold text-dark">{{ $b->product->name ?? '-' }}</td>
                        <td>{{ $b->planned_quantity ?? $b->batch_size }} {{ $b->uom->symbol ?? 'KG' }}</td>
                        <td><span class="badge bg-light text-dark border">v{{ $b->recipeVersion->version_number ?? '1.0' }}</span></td>
                        <td>{{ $b->start_date ? \Carbon\Carbon::parse($b->start_date)->format('d-m-Y') : '-' }}</td>
                        <td><span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $b->status)) }}">{{ $b->status }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('production.execution.show', $b) }}" class="btn btn-sm btn-outline-primary me-1" title="Execute Flow"><i class="bi bi-play-circle me-1"></i> Run</a>
                            <a href="{{ route('production.bmr.show', $b) }}" class="btn btn-sm btn-icon btn-outline-info me-1" title="BMR Record"><i class="bi bi-file-earmark-medical"></i></a>
                            <a href="{{ route('production.reconciliation.show', $b) }}" class="btn btn-sm btn-icon btn-outline-warning me-1" title="Reconciliation"><i class="bi bi-calculator"></i></a>
                            <a href="{{ route('production.batches.show', $b) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Details"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No batches found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $batches->firstItem() }} to {{ $batches->lastItem() }} of {{ $batches->total() }}</span>
            {{ $batches->links() }}
        </div>
        @endif
    </div>
</div>
@endsection