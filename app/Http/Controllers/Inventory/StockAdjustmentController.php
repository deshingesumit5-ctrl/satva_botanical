<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockAdjustment;
use App\Models\StockLot;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentController extends Controller
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
        $query = StockAdjustment::with(['stockLot.material', 'stockLot.product', 'warehouse', 'adjustedByUser']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('adjustment_number', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('adjustment_type', $type);
        }

        $adjustments = $query->latest()->paginate(10)->withQueryString();

        return view('inventory.adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $lots = StockLot::with(['material.baseUom', 'product.baseUom', 'warehouse'])
            ->where('status', 'Active')
            ->get();

        return view('inventory.adjustments.create', compact('lots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stock_lot_id' => 'required|exists:stock_lots,id',
            'adjustment_type' => 'required|in:Add,Subtract',
            'quantity' => 'required|numeric|min:0.001',
            'reason' => 'required|string|max:255',
        ]);

        $lot = StockLot::findOrFail($validated['stock_lot_id']);
        $adjNumber = $this->docService->generate('ADJUSTMENT');

        try {
            $this->stockService->adjust(
                $lot->id,
                $validated['adjustment_type'],
                $validated['quantity'],
                $validated['reason'],
                Auth::id()
            );

            StockAdjustment::create([
                'adjustment_number' => $adjNumber,
                'stock_lot_id' => $lot->id,
                'warehouse_id' => $lot->warehouse_id,
                'adjustment_type' => $validated['adjustment_type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'adjusted_by' => Auth::id(),
                'adjustment_date' => now(),
            ]);

            return redirect()->route('inventory.adjustments.index')
                ->with('success', "Stock adjustment {$adjNumber} ({$validated['adjustment_type']} {$validated['quantity']}) posted.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Adjustment failed: ' . $e->getMessage());
        }
    }

    public function show($id = null)
    {
        return redirect()->back();
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
