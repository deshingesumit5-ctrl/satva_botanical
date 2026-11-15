@extends('layouts.app')
@section('title', 'Add Supplier')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Add New Supplier</h2>
            <p class="page-subtitle">Register vendor, botanical collector or solvent distributor.</p>
        </div>
        <a href="{{ route('masters.suppliers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('masters.suppliers.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Supplier Code</label>
                        <input type="text" name="supplier_code" class="form-control" required placeholder="e.g. SUP-GARC-001">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label required">Supplier Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Western Ghats Botanical Harvesters Pvt Ltd">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-control" placeholder="Contact Person">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" name="mobile" class="form-control" placeholder="Mobile">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="vendor@domain.com">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="gstin" class="form-control" placeholder="27ABCDE1234F1Z5">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">PAN</label>
                        <input type="text" name="pan" class="form-control" placeholder="ABCDE1234F">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Payment Terms (Days)</label>
                        <input type="number" name="payment_terms_days" class="form-control" value="30">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Address Line</label>
                    <input type="text" name="address_line" class="form-control" placeholder="Plot / Survey No, Industrial Area">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" placeholder="City">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" placeholder="State">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-control" placeholder="Pincode">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('masters.suppliers.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection