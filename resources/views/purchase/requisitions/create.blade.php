@extends('layouts.app')
@section('title', 'Create Purchase Requisition')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">New Purchase Requisition</h2>
            <p class="page-subtitle">Indicate required raw materials, botanical herbs or solvents.</p>
        </div>
        <a href="{{ route('purchase.requisitions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('purchase.requisitions.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Plant Facility</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Requesting Department</label>
                        <select name="department_id" class="form-select" required>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Required By Date</label>
                        <input type="date" name="required_by_date" class="form-control" required value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Purpose / Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" placeholder="Explain requisition requirement, e.g. Upcoming Garcinia extract production batch..."></textarea>
                </div>

                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                    <span>Requisition Line Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPrRow()"><i class="bi bi-plus-lg me-1"></i> Add Material</button>
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="40%">Material</th>
                                <th width="20%">Required Quantity</th>
                                <th width="20%">Unit (UOM)</th>
                                <th width="20%" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="prItemsBody">
                            <tr>
                                <td>
                                    <select name="items[0][material_id]" class="form-select form-select-sm" required>
                                        <option value="">Select Material</option>
                                        @foreach($materials as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->material_code }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
                                <td>
                                    <select name="items[0][uom_id]" class="form-select form-select-sm" required>
                                        @foreach($uoms as $u)
                                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase.requisitions.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Requisition</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let prIdx = 1;
function addPrRow() {
    const tbody = document.getElementById('prItemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${prIdx}][material_id]" class="form-select form-select-sm" required>
                <option value="">Select Material</option>
                @foreach($materials as $m)
                    <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->material_code }})</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" name="items[${prIdx}][quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
        <td>
            <select name="items[${prIdx}][uom_id]" class="form-select form-select-sm" required>
                @foreach($uoms as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                @endforeach
            </select>
        </td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    prIdx++;
}
</script>
@endsection