@extends('layouts.app')
@section('title', 'Company Settings')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Company Profile & Branding</h2>
            <p class="page-subtitle">Configure legal entity, botanical licenses, tax GSTIN and print header details.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('settings.company.update') }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label required">Company Legal Name</label>
                        <input type="text" name="company_name" class="form-control" value="{{ old('company_name', $company->company_name ?? 'Sattva Botanical Pvt Ltd') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="gstin" class="form-control" value="{{ old('gstin', $company->gstin ?? '') }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">PAN</label>
                        <input type="text" name="pan" class="form-control @error('pan') is-invalid @enderror" value="{{ old('pan', $company->pan ?? '') }}" placeholder="ABCDE1234F" maxlength="10" minlength="10" pattern="[A-Za-z0-9]{10}" title="PAN must be exactly 10 alphanumeric characters">
                        @error('pan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Ayush / Drug License No</label>
                        <input type="text" name="drug_license_no" class="form-control" value="{{ old('drug_license_no', $company->drug_license_no ?? '') }}">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $company->email ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone / Mobile</label>
                        <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror @error('contact_number') is-invalid @enderror" value="{{ old('phone', $company->phone ?? $company->contact_number ?? '') }}" placeholder="9876543210" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" title="Mobile number must be exactly 10 digits">
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Registered Office Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address ?? '') }}</textarea>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control" value="{{ old('city', $company->city ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-control" value="{{ old('state', $company->state ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $company->pincode ?? '') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Company Logo (For BMR & Invoices)</label>
                    <input type="file" name="logo" class="form-control">
                    @if(!empty($company->logo))
                        <div class="mt-2"><img src="{{ asset('storage/'.$company->logo) }}" height="50" class="rounded border p-1"></div>
                    @endif
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update Company Settings?', this.closest('form'))">Save Company Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection