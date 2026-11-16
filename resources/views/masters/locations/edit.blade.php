@extends('layouts.app')
@section('title', 'Edit Storage Location')
@section('content')
    <div class="container-fluid px-0" style="max-width: 600px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Edit Storage Location</h2>
                <p class="page-subtitle">Update rack, bin or zone details.</p>
            </div>
            <a href="{{ route('masters.locations.index') }}" class="btn btn-outline-secondary"><i
                    class="bi bi-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card card-sattva">
            <div class="card-sattva-body p-4">
                <form action="{{ route('masters.locations.update', $location) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="mb-3">
                        <label class="form-label required">Location Name</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $location->name) }}"
                            required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Code</label>
                            <input type="text" class="form-control"
                                value="{{ $location->location_code ?? $location->code }}" readonly disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Warehouse</label>
                            <select name="warehouse_id" class="form-select" required>
                                @foreach(\App\Models\Warehouse::where('status', 'Active')->get() as $wh)
                                    <option value="{{ $wh->id }}" {{ $location->warehouse_id == $wh->id ? 'selected' : '' }}>
                                        {{ $wh->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Rack No</label>
                            <input type="text" name="rack_no" class="form-control"
                                value="{{ old('rack_no', $location->rack_no) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bin No</label>
                            <input type="text" name="bin_no" class="form-control"
                                value="{{ old('bin_no', $location->bin_no) }}">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active" {{ $location->status === 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ $location->status === 'Inactive' ? 'selected' : '' }}>Inactive
                            </option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('masters.locations.index') }}" class="btn btn-light">Cancel</a>
                        <button type="button" class="btn btn-primary-sattva"
                            onclick="confirmEdit('Do you want to update this Location?', this.closest('form'))">Save
                            Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection