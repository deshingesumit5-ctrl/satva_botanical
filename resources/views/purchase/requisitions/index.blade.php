@extends('layouts.app')
@section('title', 'Purchase Requisitions')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Purchase Requisitions</h2>
                <p class="page-subtitle">Internal material requests raised by plant supervisors, production managers, or
                    automated MRP.</p>
            </div>
            <a href="{{ route('purchase.requisitions.create') }}" class="btn btn-primary-sattva">
                <i class="bi bi-plus-lg me-1"></i> Create Requisition
            </a>
        </div>

        <div class="card card-sattva mb-4">
            <div class="card-sattva-body p-3">
                <form action="{{ route('purchase.requisitions.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-6"><input type="text" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Search PR number, remarks..."></div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Draft" {{ request('status') === 'Draft' ? 'selected' : '' }}>Draft</option>
                            <option value="Submitted" {{ request('status') === 'Submitted' ? 'selected' : '' }}>Submitted
                            </option>
                            <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Approved
                            </option>
                            <option value="PO Created" {{ request('status') === 'PO Created' ? 'selected' : '' }}>PO Created
                            </option>
                            <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-dark-filter flex-grow-1">Filter</button>
                        <a href="{{ route('purchase.requisitions.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>PR Number</th>
                            <th>Date</th>
                            <th>Department</th>
                            <th>Plant</th>
                            <th>Items Count</th>
                            <th>Requested By</th>
                            <th>Status</th>
                            <th width="160" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requisitions as $idx => $pr)
                            <tr>
                                <td>{{ $requisitions->firstItem() + $idx }}</td>
                                <td class="fw-bold text-success">{{ $pr->pr_number }}</td>
                                <td>{{ $pr->created_at->format('d-m-Y') }}</td>
                                <td>{{ $pr->department->name ?? '-' }}</td>
                                <td>{{ $pr->plant->name ?? '-' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $pr->items->count() }} items</span></td>
                                <td>{{ $pr->requestedByUser->name ?? '-' }}</td>
                                <td><span
                                        class="badge-status badge-status-{{ strtolower(str_replace(' ', '-', $pr->status)) }}">{{ $pr->status }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('purchase.requisitions.show', $pr) }}"
                                        class="btn btn-sm btn-icon btn-outline-secondary me-1" title="View"><i
                                            class="bi bi-eye"></i></a>
                                    @if(in_array($pr->status, ['Draft', 'Rejected']))
                                        <a href="{{ route('purchase.requisitions.edit', $pr) }}"
                                            class="btn btn-sm btn-icon btn-outline-primary me-1" title="Edit"><i
                                                class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-sm btn-icon btn-action-delete"
                                            data-url="{{ route('purchase.requisitions.destroy', $pr) }}"
                                            data-name="{{ $pr->pr_number }}" title="Delete"><i class="bi bi-trash"></i></button>
                                    @endif
                                    @if($pr->status === 'Approved')
                                        <a href="{{ route('purchase.orders.create', ['pr_id' => $pr->id]) }}"
                                            class="btn btn-sm btn-outline-success me-1" title="Generate PO"><i
                                                class="bi bi-cart-plus me-1"></i> Create PO</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No purchase requisitions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requisitions->hasPages())
                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted small">Showing {{ $requisitions->firstItem() }} to {{ $requisitions->lastItem() }}
                        of {{ $requisitions->total() }}</span>
                    {{ $requisitions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection