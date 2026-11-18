@extends('layouts.app')
@section('title', 'Edit Customer')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Edit Customer: {{ $customer->name }}</h2>
                <p class="page-subtitle">Update customer contacts, billing, and credit parameters.</p>
            </div>
            <a href="{{ route('masters.customers.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('masters.customers.update', $customer) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Customer Code</label>
                            <input type="text" class="form-control"
                                value="{{ $customer->code ?? $customer->customer_code }}" disabled>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Customer / Company Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}"
                                required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control"
                                value="{{ old('contact_person', $customer->contact_person) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
                                value="{{ old('mobile', $customer->mobile) }}" placeholder="9876543210" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" title="Mobile number must be exactly 10 digits">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $customer->email) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin" class="form-control"
                                value="{{ old('gstin', $customer->gstin) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PAN</label>
                            <input type="text" name="pan" class="form-control @error('pan') is-invalid @enderror" value="{{ old('pan', $customer->pan) }}" placeholder="ABCDE1234F" maxlength="10" minlength="10" pattern="[A-Za-z0-9]{10}" title="PAN must be exactly 10 alphanumeric characters">
                            @error('pan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Credit Limit (INR)</label>
                            <input type="number" step="0.01" name="credit_limit" class="form-control"
                                value="{{ old('credit_limit', $customer->credit_limit) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Billing Address</label>
                            <textarea name="billing_address" class="form-control"
                                rows="2">{{ old('billing_address', $customer->billing_address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Shipping Address</label>
                            <textarea name="shipping_address" class="form-control"
                                rows="2">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control"
                                value="{{ old('state', $customer->state) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Terms (Days)</label>
                            <input type="number" name="payment_terms_days" class="form-control"
                                value="{{ old('payment_terms_days', $customer->payment_terms_days) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" {{ $customer->status === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $customer->status === 'Inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('masters.customers.index') }}" class="btn btn-light">Cancel</a>
                        <button type="button" class="btn btn-primary-sattva"
                            onclick="confirmEdit('Do you want to update this Customer?', this.closest('form'))">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection