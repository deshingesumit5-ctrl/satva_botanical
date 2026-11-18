@extends('layouts.app')
@section('title', 'Process Waste & Residue')
@section('content')
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="page-title">Process Waste & Hazardous Residue Register</h2>
                <p class="page-subtitle">Track spent botanical marc, distillation boiler sludge, ETP effluent, and hazardous
                    waste disposal.</p>
            </div>
            @if(auth()->user() && auth()->user()->hasPermission('inventory.waste.add'))
                <button type="button" class="btn btn-primary-sattva" data-bs-toggle="modal" data-bs-target="#addWasteModal">
                    <i class="bi bi-plus-lg me-1"></i> Record Waste / Residue
                </button>
            @endif
        </div>

        <div class="card card-sattva">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Waste ID</th>
                            <th>Production Batch</th>
                            <th>Waste Category</th>
                            <th>Quantity Generated</th>
                            <th>Disposal Method / Remarks</th>
                            <th>Disposal Date</th>
                            <th>Authorized Signatory</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wasteRecords as $idx => $w)
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td class="fw-bold font-monospace">
                                    {{ $w->waste_code ?? 'WST-' . str_pad($w->id, 5, '0', STR_PAD_LEFT) }}</td>
                                <td class="fw-bold text-success">
                                    {{ $w->batch->batch_number ?? $w->productionBatch->batch_number ?? '-' }}</td>
                                <td><span
                                        class="badge bg-light text-dark border">{{ $w->waste_type ?? $w->waste_category ?? 'Spent Material' }}</span>
                                </td>
                                <td class="fw-bold">{{ $w->quantity }} {{ $w->uom->uom_code ?? $w->uom ?? 'KG' }}</td>
                                <td>{{ $w->remarks ?? $w->disposal_method ?? 'Bio-Composting / Incineration' }}</td>
                                <td>{{ ($w->disposal_date ?? $w->disposed_at) ? \Carbon\Carbon::parse($w->disposal_date ?? $w->disposed_at)->format('d-m-Y') : '-' }}
                                </td>
                                <td>{{ $w->authorizedBy->name ?? 'Plant Officer' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No process waste records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-form-modal id="addWasteModal" title="Record Process Waste / Residue" size="lg"
        :action="route('inventory.waste.store')" method="POST" :hasForm="true" submitText="Save">
        <div class="mb-3">
            <label class="form-label required">Production Batch</label>
            <select name="batch_id" class="form-select" required>
                <option value="">Select Batch</option>
                @foreach($batches ?? [] as $b)
                    <option value="{{ $b->id }}">{{ $b->batch_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label required">Waste Category</label>
                <select name="waste_type" class="form-select" required>
                    <option value="Spent Material">Spent Material</option>
                    <option value="Waste Material">Waste Material</option>
                    <option value="Saleable By-product">Saleable By-product</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label required">Item Name</label>
                <input type="text" name="item_name" class="form-control" placeholder="e.g. Spent Garcinia Marc" required>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label required">Quantity</label>
                <input type="number" step="0.001" name="quantity" class="form-control" placeholder="e.g. 250" required>
            </div>
            <div class="col-6">
                <label class="form-label required">UOM</label>
                <select name="uom_id" class="form-select" required>
                    @foreach($uoms ?? [] as $u)
                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->uom_code }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-2 mb-3">
            <div class="col-6">
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">Select Warehouse</option>
                    @foreach($warehouses ?? [] as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Disposal Date</label>
                <input type="date" name="disposal_date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label">Disposal Method / Remarks</label>
            <input type="text" name="remarks" class="form-control" placeholder="e.g. Bio-composting / ETP disposal">
        </div>
    </x-form-modal>
@endsection