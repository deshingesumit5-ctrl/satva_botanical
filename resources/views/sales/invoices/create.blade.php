@extends('layouts.app')
@section('title', 'Generate Sales Invoice')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Generate GST Sales Invoice</h2>
            <p class="page-subtitle">Create commercial billing from delivery dispatch notes.</p>
        </div>
        <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.invoices.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Delivery Dispatch Note</label>
                        <select name="dispatch_id" class="form-select" onchange="window.location='{{ route('sales.invoices.create') }}?dispatch_id=' + this.value" required>
                            <option value="">Select Dispatch Challan</option>
                            @foreach($dispatches as $d)
                                <option value="{{ $d->id }}" {{ $selectedDispatch && $selectedDispatch->id == $d->id ? 'selected' : '' }}>
                                    {{ $d->dispatch_number }} | {{ $d->customer->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $selectedDispatch && $selectedDispatch->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Invoice Date</label>
                        <input type="date" name="invoice_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Due Date</label>
                        <input type="date" name="due_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Terms</label>
                        <input type="text" name="payment_terms" class="form-control" value="Net 30 Days">
                    </div>
                </div>

                @if($selectedDispatch)
                <h6 class="fw-bold mb-3">Invoiced Items</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Dispatched Qty</th>
                                <th width="180">Unit Rate (INR) *</th>
                                <th width="120">GST %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedDispatch->items as $idx => $it)
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $it->product->name ?? '-' }}</div>
                                    <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $it->product_id }}">
                                    <input type="hidden" name="items[{{ $idx }}][quantity]" value="{{ $it->quantity }}">
                                    <input type="hidden" name="items[{{ $idx }}][uom_id]" value="{{ $it->uom_id }}">
                                </td>
                                <td>{{ $it->quantity }} {{ $it->uom->symbol ?? '' }}</td>
                                <td><input type="number" step="0.01" name="items[{{ $idx }}][unit_price]" class="form-control form-control-sm" value="500.00" required></td>
                                <td><input type="number" step="0.01" name="items[{{ $idx }}][tax_percent]" class="form-control form-control-sm" value="18.00"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.invoices.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Generate Tax Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection