@extends('layouts.app')
@section('title', 'System Settings')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">System & Production Policy Settings</h2>
            <p class="page-subtitle">Configure session limits, inventory valuation defaults, and batch numbering rules.</p>
        </div>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('settings.system.update') }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Session Timeout (Minutes)</label>
                        <input type="number" name="session_timeout_minutes" class="form-control" value="{{ old('session_timeout_minutes', $system->session_timeout_minutes ?? 120) }}" min="15">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Default Valuation Method</label>
                        <select name="valuation_method" class="form-select" required>
                            <option value="FIFO" {{ ($system->valuation_method ?? 'FIFO') === 'FIFO' ? 'selected' : '' }}>FIFO (First In First Out)</option>
                            <option value="Weighted Average" {{ ($system->valuation_method ?? '') === 'Weighted Average' ? 'selected' : '' }}>Weighted Average Cost</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Timezone</label>
                        <input type="text" name="timezone" class="form-control" value="{{ old('timezone', $system->timezone ?? 'Asia/Kolkata') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Currency</label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency', $system->currency ?? 'INR') }}">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">Date Format</label>
                        <input type="text" name="date_format" class="form-control" value="{{ old('date_format', $system->date_format ?? 'd-m-Y') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Near Expiry Alert (Days)</label>
                        <input type="number" name="near_expiry_alert_days" class="form-control" value="{{ old('near_expiry_alert_days', $system->near_expiry_alert_days ?? 60) }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update System Settings?', this.closest('form'))">Save System Settings</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection