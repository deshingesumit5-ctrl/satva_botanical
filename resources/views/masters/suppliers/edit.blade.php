@extends('layouts.app')
@section('title', 'Edit Supplier')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Edit Supplier: {{ $supplier->name }}</h2>
                <p class="page-subtitle">Update vendor contact, GSTIN and payment terms.</p>
            </div>
            <a href="{{ route('masters.suppliers.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('masters.suppliers.update', $supplier) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Supplier Code</label>
                            <input type="text" class="form-control"
                                value="{{ $supplier->code ?? $supplier->supplier_code }}" disabled>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required">Supplier Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name) }}"
                                required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control"
                                value="{{ old('contact_person', $supplier->contact_person) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mobile Number</label>
                            <input type="text" name="mobile" class="form-control @error('mobile') is-invalid @enderror"
                                value="{{ old('mobile', $supplier->mobile) }}" placeholder="9876543210" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" title="Mobile number must be exactly 10 digits">
                            @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $supplier->email) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">GSTIN</label>
                            <input type="text" name="gstin" class="form-control"
                                value="{{ old('gstin', $supplier->gstin) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PAN</label>
                            <input type="text" name="pan" class="form-control @error('pan') is-invalid @enderror" value="{{ old('pan', $supplier->pan) }}" placeholder="ABCDE1234F" maxlength="10" minlength="10" pattern="[A-Za-z0-9]{10}" title="PAN must be exactly 10 alphanumeric characters">
                            @error('pan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control"
                                value="{{ old('payment_terms', $supplier->payment_terms ?? $supplier->payment_terms_days) }}"
                                placeholder="e.g. 30 Days">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control"
                            rows="2">{{ old('address', $supplier->address ?? $supplier->address_line) }}</textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $supplier->city) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control"
                                value="{{ old('state', $supplier->state) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" class="form-control"
                                value="{{ old('pincode', $supplier->pincode) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" {{ $supplier->status === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $supplier->status === 'Inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('masters.suppliers.index') }}" class="btn btn-light">Cancel</a>
                        <button type="button" class="btn btn-primary-sattva"
                            onclick="confirmEdit('Do you want to update this Supplier?', this.closest('form'))">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection