@extends('layouts.app')
@section('title', 'Edit Sales Order')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Order: {{ $order->order_number }}</h2>
            <p class="page-subtitle">Update customer sales order lines and delivery dates.</p>
        </div>
        <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('sales.orders.update', $order) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Customer / Client</label>
                        <select name="customer_id" class="form-select" required>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $order->customer_id == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Committed Delivery Date</label>
                        <input type="date" name="delivery_date" class="form-control" required value="{{ old('delivery_date', $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Payment Terms & Instructions</label>
                    <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', $order->payment_terms) }}">
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('sales.orders.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Sales Order?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection