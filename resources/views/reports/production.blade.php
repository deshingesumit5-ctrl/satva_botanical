@extends('layouts.app')
@section('title', 'Production Yield & Campaign Report')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Production Yield & Campaign Report</h2>
            <p class="page-subtitle">Historical output analysis, actual vs planned batch yields, and extraction cycle times.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reports.production.pdf') }}" class="btn btn-outline-info"><i class="bi bi-printer me-1"></i> Export PDF</a>
        </div>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('reports.production') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <select name="product_id" class="form-select">
                        <option value="">All Finished Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="In Progress" {{ request('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('reports.production') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Product</th>
                        <th>Recipe Ver</th>
                        <th>Planned Qty</th>
                        <th>Actual Output</th>
                        <th>Yield %</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $idx => $b)
                    @php
                        $planned = (float)($b->planned_quantity ?? $b->batch_size);
                        $actual = (float)($b->actual_quantity ?? 0);
                        $yield = $planned > 0 ? round(($actual / $planned) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold font-monospace text-success">{{ $b->batch_no ?? $b->batch_number }}</td>
                        <td class="fw-semibold text-dark">{{ $b->product->name ?? '-' }}</td>
                        <td>v{{ $b->recipeVersion->version_number ?? '1.0' }}</td>
                        <td>{{ $planned }} {{ $b->uom->symbol ?? 'KG' }}</td>
                        <td class="fw-bold">{{ $actual }} {{ $b->uom->symbol ?? 'KG' }}</td>
                        <td>
                            <span class="badge {{ $yield >= 95 ? 'bg-success' : ($yield >= 80 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $yield }}%
                            </span>
                        </td>
                        <td><span class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $b->status)) }}">{{ $b->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">No batch production records found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection