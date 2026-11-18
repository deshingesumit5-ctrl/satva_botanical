@extends('layouts.app')
@section('title', 'Add Customer')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Add New Customer</h2>
            <p class="page-subtitle">Register pharmaceutical client, distributor or brand.</p>
        </div>
        <a href="{{ route('masters.customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.customers.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Customer Code</label>
                        <input type="text" name="customer_code" class="form-control" required placeholder="e.g. CUST-NUTR-001">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Customer / Company Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. PureHealth Nutra Corp">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="Contact Person">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" value="{{ old('mobile') }}" class="form-control @error('mobile') is-invalid @enderror" placeholder="9876543210" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" title="Mobile number must be exactly 10 digits">
                        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="purchase@customer.com">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="gstin" value="{{ old('gstin') }}" class="form-control" placeholder="27ABCDE1234F1Z5">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN</label>
                        <input type="text" name="pan" value="{{ old('pan') }}" class="form-control @error('pan') is-invalid @enderror" placeholder="ABCDE1234F" maxlength="10" minlength="10" pattern="[A-Za-z0-9]{10}" title="PAN must be exactly 10 alphanumeric characters">
                        @error('pan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Credit Limit (INR)</label>
                        <input type="number" step="0.01" name="credit_limit" class="form-control" value="0.00">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Billing Address</label>
                        <textarea name="billing_address" class="form-control" rows="2" placeholder="Full Billing Address..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Shipping Address</label>
                        <textarea name="shipping_address" class="form-control" rows="2" placeholder="Full Delivery / Factory Shipping Address..."></textarea>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="City">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" placeholder="State">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment Terms (Days)</label>
                        <input type="number" name="payment_terms_days" class="form-control" value="30">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.customers.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection