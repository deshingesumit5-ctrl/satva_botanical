@extends('layouts.app')
@section('title', 'Document Numbering')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Document Numbering Master</h2>
            <p class="page-subtitle">Configure prefixes, serial digits and numbering formats for PO, GRN, BMR, Batch, and Invoices.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60">#</th>
                        <th>Document Type</th>
                        <th>Prefix</th>
                        <th>Suffix</th>
                        <th>Min Digits</th>
                        <th>Current Counter</th>
                        <th>Sample Preview</th>
                        <th width="120" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($numberings as $idx => $n)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="fw-bold text-dark">{{ $n->document_type }}</td>
                        <td><code>{{ $n->prefix }}</code></td>
                        <td><code>{{ $n->suffix ?? '-' }}</code></td>
                        <td>{{ $n->digits }}</td>
                        <td>{{ $n->current_number }}</td>
                        <td><span class="badge bg-light text-success border font-monospace">{{ $n->prefix }}{{ str_pad($n->current_number + 1, $n->digits, '0', STR_PAD_LEFT) }}{{ $n->suffix }}</span></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editNumModal{{ $n->id }}">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@foreach($numberings as $n)
<div class="modal fade" id="editNumModal{{ $n->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('settings.document-numbering.update', $n) }}" method="POST" class="modal-content">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Numbering: {{ $n->document_type }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Prefix</label>
                    <input type="text" name="prefix" class="form-control" value="{{ $n->prefix }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Suffix</label>
                    <input type="text" name="suffix" class="form-control" value="{{ $n->suffix }}">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Total Digits</label>
                        <input type="number" name="digits" class="form-control" value="{{ $n->digits }}" min="3" max="8" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Current Counter</label>
                        <input type="number" name="current_number" class="form-control" value="{{ $n->current_number }}" min="0" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary-sattva">Save Numbering</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection