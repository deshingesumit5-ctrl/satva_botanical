<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Product;
use App\Models\StockLedger;
use App\Models\StockLot;
use App\Models\StorageLocation;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    protected StockService $stockService;
    protected DocumentNumberService $docService;

    public function __construct(StockService $stockService, DocumentNumberService $docService)
    {
        $this->stockService = $stockService;
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = StockLot::with(['material.baseUom', 'product.baseUom', 'warehouse', 'storageLocation'])
            ->where('status', 'Active')
            ->where('available_qty', '>', 0);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('material_code', 'like', "%{$search}%");
                  })
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('product_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        $lots = $query->latest()->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('status', 'Active')->get();

        return view('inventory.stock.index', compact('lots', 'warehouses'));
    }

    public function ledger(Request $request)
    {
        $query = StockLedger::with(['material', 'product', 'warehouse', 'stockLot', 'creator']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                  ->orWhere('reference_no', 'like', "%{$search}%")
                  ->orWhere('transaction_type', 'like', "%{$search}%");
            });
        }

        if ($materialId = $request->input('material_id')) {
            $query->where('material_id', $materialId);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($fromDate = $request->input('from_date')) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if ($toDate = $request->input('to_date')) {
            $query->whereDate('transaction_date', '<=', $toDate);
        }

        $ledgerEntries = $query->latest('id')->paginate(25)->withQueryString();
        $materials = Material::where('status', 'Active')->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();

        return view('inventory.stock.ledger', compact('ledgerEntries', 'materials', 'warehouses'));
    }

    public function lots(Request $request)
    {
        $query = StockLot::with(['material.baseUom', 'product.baseUom', 'warehouse', 'storageLocation', 'supplier']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                  ->orWhere('qc_status', 'like', "%{$search}%")
                  ->orWhereHas('material', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($qcStatus = $request->input('qc_status')) {
            $query->where('qc_status', $qcStatus);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        $lots = $query->latest()->paginate(20)->withQueryString();
        $warehouses = Warehouse::where('status', 'Active')->get();

        return view('inventory.stock.lots', compact('lots', 'warehouses'));
    }

    public function openingEntryForm()
    {
        $materials = Material::where('status', 'Active')->orderBy('name')->get();
        $products = Product::where('status', 'Active')->orderBy('name')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();
        $locations = StorageLocation::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('inventory.stock.opening-entry', compact('materials', 'products', 'warehouses', 'locations', 'uoms'));
    }

    public function openingEntry(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|in:material,product',
            'material_id' => 'required_if:item_type,material|nullable|exists:materials,id',
            'product_id' => 'required_if:item_type,product|nullable|exists:products,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'storage_location_id' => 'nullable|exists:storage_locations,id',
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'mfg_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:mfg_date',
            'custom_lot_number' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        $lotNumber = !empty($validated['custom_lot_number']) ? $validated['custom_lot_number'] : $this->docService->generate('LOT');

        $stockLot = $this->stockService->inward([
            'lot_number' => $lotNumber,
            'material_id' => $validated['material_id'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'],
            'storage_location_id' => $validated['storage_location_id'] ?? null,
            'quantity' => $validated['quantity'],
            'available_qty' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'],
            'qc_status' => 'QC Approved',
            'mfg_date' => $validated['mfg_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'txn_type' => 'Opening Stock',
            'remarks' => $validated['remarks'] ?? 'Initial Opening Stock Entry',
        ]);

        AuditService::log('inventory', $stockLot->lot_number, 'opening_stock_entry', null, $stockLot->toArray(), 'Recorded opening stock balance');

        return redirect()->route('inventory.stock.lots')->with('success', "Opening stock lot '{$stockLot->lot_number}' recorded successfully.");
    }
}
