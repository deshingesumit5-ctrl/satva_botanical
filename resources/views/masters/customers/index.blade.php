@extends('layouts.app')
@section('title', 'Customer Master')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Customer & Client Master</h2>
            <p class="page-subtitle">Manage nutraceutical brands, pharmaceutical formulators, overseas clients and credit limits.</p>
        </div>
        <a href="{{ route('masters.customers.create') }}" class="btn btn-primary-sattva">
            <i class="bi bi-plus-lg me-1"></i> Add Customer
        </a>
    </div>

    <div class="card card-sattva mb-4">
        <div class="card-sattva-body p-3">
            <form action="{{ route('masters.customers.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search customer name, code, GSTIN, city..."></div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                    <a href="{{ route('masters.customers.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Customer Code</th>
                        <th>Customer Name</th>
                        <th>Contact Person</th>
                        <th>Mobile / Email</th>
                        <th>GSTIN</th>
                        <th>Credit Limit</th>
                        <th>Status</th>
                        <th width="140" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $idx => $cust)
                    <tr>
                        <td>{{ $customers->firstItem() + $idx }}</td>
                        <td class="fw-bold text-success">{{ $cust->customer_code }}</td>
                        <td class="fw-semibold text-dark">{{ $cust->name }}</td>
                        <td>{{ $cust->contact_person ?? '-' }}</td>
                        <td>
                            <div>{{ $cust->mobile ?? '-' }}</div>
                            <small class="text-muted">{{ $cust->email }}</small>
                        </td>
                        <td><code>{{ $cust->gstin ?? '-' }}</code></td>
                        <td>₹{{ number_format($cust->credit_limit ?? 0, 2) }}</td>
                        <td>
                            <form action="{{ route('masters.customers.toggle-status', $cust) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="border-0 bg-transparent p-0" onclick="confirmAction('Toggle status for {{ $cust->name }}?', this.closest('form'))">
                                    <span class="badge-status badge-status-{{ strtolower($cust->status) }}">{{ $cust->status }}</span>
                                </button>
                            </form>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('masters.customers.show', $cust) }}" class="btn btn-sm btn-icon btn-outline-secondary me-1"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('masters.customers.edit', $cust) }}" class="btn btn-sm btn-icon btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('masters.customers.destroy', $cust) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="confirmDelete('Do you want to delete this Customer?', '{{ $cust->name }}', this.closest('form'))"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="text-muted small">Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ $customers->total() }}</span>
            {{ $customers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection