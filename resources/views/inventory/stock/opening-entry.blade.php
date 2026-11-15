@extends('layouts.app')
@section('title', 'Opening Stock Entry')
@section('content')
<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title">Opening Stock Entry</h2>
            <p class="page-subtitle">Initial stock registration for starting warehouse inventory balances.</p>
        </div>
        <a href="{{ route('inventory.stock.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Back</a>
    </div>

    <div class="card card-sattva">
        <div class="card-sattva-body p-4">
            <form action="{{ route('inventory.stock.store-opening-entry') }}" method="POST">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Item Type</label>
                        <select name="item_type" class="form-select" id="itemTypeSelect" onchange="toggleItemType(this.value)" required>
                            <option value="material">Raw Material / Solvent</option>
                            <option value="product">Finished Good Product</option>
                        </select>
                    </div>
                    <div class="col-md-6" id="matDiv">
                        <label class="form-label required">Select Material</label>
                        <select name="material_id" class="form-select">
                            <option value="">Select Material</option>
                            @foreach($materials as $m)
                                <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->material_code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-none" id="prodDiv">
                        <label class="form-label required">Select Product</label>
                        <select name="product_id" class="form-select">
                            <option value="">Select Product</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->product_code }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label required">Target Warehouse</label>
                        <select name="warehouse_id" class="form-select" required>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Storage Location / Rack</label>
                        <select name="storage_location_id" class="form-select">
                            <option value="">None / Default</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label required">Opening Quantity</label>
                        <input type="number" step="0.001" name="quantity" class="form-control" required placeholder="Quantity">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">UOM</label>
                        <select name="uom_id" class="form-select" required>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Unit Valuation Cost (INR)</label>
                        <input type="number" step="0.01" name="unit_cost" class="form-control" required placeholder="Cost per unit">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Lot Number (Auto if blank)</label>
                        <input type="text" name="lot_number" class="form-control" placeholder="Auto-generated if empty">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('inventory.stock.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary-sattva">Save Opening Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleItemType(type) {
    if (type === 'product') {
        document.getElementById('matDiv').classList.add('d-none');
        document.getElementById('prodDiv').classList.remove('d-none');
    } else {
        document.getElementById('prodDiv').classList.add('d-none');
        document.getElementById('matDiv').classList.remove('d-none');
    }
}
</script>
@endsection