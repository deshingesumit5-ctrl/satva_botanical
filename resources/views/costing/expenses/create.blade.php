@extends('layouts.app')
@section('title', 'Log Expense Voucher')
@section('content')
<div class="container-fluid px-0" style="max-width: 650px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Log Overhead Expense Voucher</h2>
            <p class="page-subtitle">Record electricity, boiler fuel or direct plant labor expenses.</p>
        </div>
        <a href="{{ route('costing.expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('costing.expenses.store') }}" method="POST">
                @csrf
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Month</label>
                        <select name="month" class="form-select" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Year</label>
                        <input type="number" name="year" class="form-control" value="{{ date('Y') }}" required>
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Expense Category</label>
                        <select name="expense_category" class="form-select" required>
                            <option value="Electricity">Electricity (HT Substation)</option>
                            <option value="Boiler Fuel">Boiler Fuel / Steam Briquettes</option>
                            <option value="Direct Labor">Direct Factory Labor</option>
                            <option value="Solvent Losses">Solvent Make-up / Loss</option>
                            <option value="QC & Lab Testing">QC Reagents & Standards</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Plant Facility</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Total Incurred Amount (INR)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                </div>

                <div class="mb-4">
                    <label class="form-label">Bill Reference / Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="e.g. MSEDCL Electricity Bill for Extraction Plant 1"></textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('costing.expenses.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Expense Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection