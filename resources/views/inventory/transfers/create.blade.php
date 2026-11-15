@extends('layouts.app')
@section('title', 'New Stock Transfer')
@section('content')
<div class="container-fluid px-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">New Stock Transfer</h2>
            <p class="page-subtitle">Move lots from source warehouse to destination warehouse.</p>
        </div>
        <a href="{{ route('inventory.transfers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('inventory.transfers.store') }}" method="POST">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label required">From Source Warehouse</label>
                        <select name="from_warehouse_id" class="form-select" required>
                            <option value="">Select Source Warehouse</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">To Destination Warehouse</label>
                        <select name="to_warehouse_id" class="form-select" required>
                            <option value="">Select Destination Warehouse</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Transfer Purpose / Reason</label>
                    <input type="text" name="remarks" class="form-control" placeholder="e.g. Issue from RM Godown to Solvent Extraction Floor">
                </div>

                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                    <span>Transfer Stock Lots</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTransferRow()"><i class="bi bi-plus-lg me-1"></i> Add Lot</button>
                </h6>

                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="45%">Available Stock Lot</th>
                                <th width="20%">Transfer Quantity</th>
                                <th width="25%">Dest Storage Location</th>
                                <th width="10%" class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody id="transferItemsBody">
                            <tr>
                                <td>
                                    <select name="items[0][stock_lot_id]" class="form-select form-select-sm" required>
                                        <option value="">Select Lot</option>
                                        @foreach($lots as $lot)
                                            <option value="{{ $lot->id }}">
                                                {{ $lot->lot_number }} | {{ $lot->material->name ?? $lot->product->name ?? '' }} (Avail: {{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" step="0.001" name="items[0][quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
                                <td>
                                    <select name="items[0][to_location_id]" class="form-select form-select-sm">
                                        <option value="">Default Location</option>
                                        @foreach($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('inventory.transfers.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Execute Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let tIdx = 1;
function addTransferRow() {
    const tbody = document.getElementById('transferItemsBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${tIdx}][stock_lot_id]" class="form-select form-select-sm" required>
                <option value="">Select Lot</option>
                @foreach($lots as $lot)
                    <option value="{{ $lot->id }}">
                        {{ $lot->lot_number }} | {{ $lot->material->name ?? $lot->product->name ?? '' }} (Avail: {{ $lot->current_quantity }} {{ $lot->uom->symbol ?? '' }})
                    </option>
                @endforeach
            </select>
        </td>
        <td><input type="number" step="0.001" name="items[${tIdx}][quantity]" class="form-control form-control-sm" required placeholder="Quantity"></td>
        <td>
            <select name="items[${tIdx}][to_location_id]" class="form-select form-select-sm">
                <option value="">Default Location</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-icon btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    tIdx++;
}
</script>
@endsection