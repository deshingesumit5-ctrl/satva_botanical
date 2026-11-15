<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockLot;
use App\Models\StockTransfer;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
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
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'material', 'product', 'stockLot', 'transferredByUser']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        $transfers = $query->latest()->paginate(10)->withQueryString();

        return view('inventory.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('status', 'Active')->get();
        $locations = StorageLocation::where('status', 'Active')->get();
        $lots = StockLot::with(['material', 'product', 'warehouse'])
            ->where('status', 'Active')
            ->where('available_qty', '>', 0)
            ->get();

        return view('inventory.transfers.create', compact('warehouses', 'locations', 'lots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stock_lot_id' => 'required|exists:stock_lots,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'to_storage_location_id' => 'nullable|exists:storage_locations,id',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|string|max:255',
        ]);

        $sourceLot = StockLot::findOrFail($validated['stock_lot_id']);

        if ($sourceLot->warehouse_id == $validated['to_warehouse_id']) {
            return back()->withInput()->with('error', 'Destination warehouse must be different from source warehouse.');
        }

        if ((float)$sourceLot->available_qty < (float)$validated['quantity']) {
            return back()->withInput()->with('error', "Cannot transfer {$validated['quantity']}. Only {$sourceLot->available_qty} available in lot {$sourceLot->lot_number}.");
        }

        $transferNumber = $this->docService->generate('TRANSFER');

        try {
            $result = $this->stockService->transfer(
                $sourceLot->id,
                $validated['to_warehouse_id'],
                $validated['to_storage_location_id'] ?? null,
                $validated['quantity'],
                $validated['reason'],
                Auth::id()
            );

            StockTransfer::create([
                'transfer_number' => $transferNumber,
                'from_warehouse_id' => $sourceLot->warehouse_id,
                'from_storage_location_id' => $sourceLot->storage_location_id,
                'to_warehouse_id' => $validated['to_warehouse_id'],
                'to_storage_location_id' => $validated['to_storage_location_id'] ?? null,
                'material_id' => $sourceLot->material_id,
                'product_id' => $sourceLot->product_id,
                'stock_lot_id' => $sourceLot->id,
                'quantity' => $validated['quantity'],
                'transfer_date' => now(),
                'status' => 'Completed',
                'reason' => $validated['reason'],
                'transferred_by' => Auth::id(),
            ]);

            return redirect()->route('inventory.transfers.index')->with('success', "Stock transfer {$transferNumber} completed successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Transfer failed: ' . $e->getMessage());
        }
    }

    public function show(StockTransfer $transfer)
    {
        $transfer->load(['fromWarehouse', 'toWarehouse', 'fromLocation', 'toLocation', 'material', 'product', 'stockLot', 'transferredByUser']);
        return view('inventory.transfers.show', compact('transfer'));
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
