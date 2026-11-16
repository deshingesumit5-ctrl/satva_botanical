@extends('layouts.app')
@section('title', 'Edit QC Test')
@section('content')
    <div class="container-fluid px-0" style="max-width: 600px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Edit QC Test</h2>
                <p class="page-subtitle">Update QC test limits and standard methods.</p>
            </div>
            <a href="{{ route('masters.qc-tests.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('masters.qc-tests.update', $qcTest) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label required">Test Name</label>
                        <input type="text" name="test_name" class="form-control"
                            value="{{ old('test_name', $qcTest->test_name ?? $qcTest->name) }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Test Code</label>
                            <input type="text" class="form-control" value="{{ $qcTest->test_code ?? $qcTest->code }}"
                                disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Test Category</label>
                            <select name="test_category" class="form-select" required>
                                <option value="Chemical" {{ ($qcTest->test_category === 'Chemical' || $qcTest->test_type === 'Chemical') ? 'selected' : '' }}>Chemical</option>
                                <option value="Microbiological" {{ ($qcTest->test_category === 'Microbiological' || $qcTest->test_type === 'Microbiological') ? 'selected' : '' }}>Microbiological</option>
                                <option value="Physical" {{ ($qcTest->test_category === 'Physical' || $qcTest->test_type === 'Physical') ? 'selected' : '' }}>Physical</option>
                                <option value="Instrumental" {{ ($qcTest->test_category === 'Instrumental' || $qcTest->test_type === 'Instrumental') ? 'selected' : '' }}>Instrumental</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Result Type</label>
                            <select name="result_type" class="form-select" required>
                                <option value="Numeric" {{ $qcTest->result_type === 'Numeric' ? 'selected' : '' }}>Numeric
                                </option>
                                <option value="Text" {{ $qcTest->result_type === 'Text' ? 'selected' : '' }}>Text</option>
                                <option value="Pass-Fail" {{ $qcTest->result_type === 'Pass-Fail' ? 'selected' : '' }}>
                                    Pass-Fail</option>
                                <option value="Dropdown" {{ $qcTest->result_type === 'Dropdown' ? 'selected' : '' }}>Dropdown
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Standard Min</label>
                            <input type="number" step="0.001" name="standard_min" class="form-control"
                                value="{{ old('standard_min', $qcTest->standard_min) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Standard Max</label>
                            <input type="number" step="0.001" name="standard_max" class="form-control"
                                value="{{ old('standard_max', $qcTest->standard_max) }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Test Method</label>
                            <input type="text" name="test_method" class="form-control"
                                value="{{ old('test_method', $qcTest->test_method) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" {{ $qcTest->status === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ $qcTest->status === 'Inactive' ? 'selected' : '' }}>Inactive
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('masters.qc-tests.index') }}" class="btn btn-light">Cancel</a>
                        <button type="button" class="btn btn-primary-sattva"
                            onclick="confirmEdit('Do you want to update this QC Test?', this.closest('form'))">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection