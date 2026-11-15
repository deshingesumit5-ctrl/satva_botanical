@extends('layouts.app')
@section('title', 'Edit Purchase Requisition')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Edit Requisition: {{ $requisition->pr_number }}</h2>
            <p class="page-subtitle">Update indent details before approval.</p>
        </div>
        <a href="{{ route('purchase.requisitions.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('purchase.requisitions.update', $requisition) }}" method="POST">
                @csrf @method('PUT')
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label required">Plant Facility</label>
                        <select name="plant_id" class="form-select" required>
                            @foreach($plants as $p)
                                <option value="{{ $p->id }}" {{ $requisition->plant_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Requesting Department</label>
                        <select name="department_id" class="form-select" required>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ $requisition->department_id == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Required By Date</label>
                        <input type="date" name="required_by_date" class="form-control" required value="{{ old('required_by_date', $requisition->required_by_date ? \Carbon\Carbon::parse($requisition->required_by_date)->format('Y-m-d') : '') }}">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Purpose / Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $requisition->remarks) }}</textarea>
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
                            @foreach($requisition->items as $idx => $item)
                            <tr>
                                <td>
                                    <select name="items[{{ $idx }}][material_id]" class="form-select form-select-sm" required>
                                        @foreach($materials as $m)
                                            <option value="{{ $m->id }}" {{ $item->material_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" value="{{ $item->quantity }}" required></td>
                                <td>
                                    <select name="items[{{ $idx }}][uom_id]" class="form-select form-select-sm" required>
                                        @foreach($uoms as $u)
                                            <option value="{{ $u->id }}" {{ $item->uom_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('purchase.requisitions.index') }}" class="btn btn-light">Cancel</a>
                    <button type="button" class="btn btn-primary-sattva" onclick="confirmEdit('Do you want to update this Requisition?', this.closest('form'))">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let prIdx = {{ $requisition->items->count() + 1 }};
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