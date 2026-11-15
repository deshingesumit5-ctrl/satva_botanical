<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\DispatchItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockLot;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispatchController extends Controller
{
    protected DocumentNumberService $docService;
    protected StockService $stockService;

    public function __construct(DocumentNumberService $docService, StockService $stockService)
    {
        $this->docService = $docService;
        $this->stockService = $stockService;
    }

    public function index(Request $request)
    {
        $query = Dispatch::with(['customer', 'salesOrder', 'items.product', 'items.stockLot']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('dispatch_number', 'like', "%{$search}%")
                  ->orWhere('transporter_name', 'like', "%{$search}%")
                  ->orWhere('vehicle_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $dispatches = $query->latest()->paginate(10)->withQueryString();

        return view('sales.dispatch.index', compact('dispatches'));
    }

    public function create(Request $request)
    {
        $orders = SalesOrder::whereIn('status', ['Approved', 'Dispatched'])->with(['customer', 'items.product'])->get();
        $customers = Customer::where('status', 'Active')->get();

        $selectedOrder = null;
        if ($orderId = $request->input('order_id')) {
            $selectedOrder = SalesOrder::with(['customer', 'items.product.baseUom', 'items.uom'])->find($orderId);
        }

        // Get available Finished Goods stock lots
        $availableLots = StockLot::with('product')
            ->whereNotNull('product_id')
            ->where('status', 'Active')
            ->where('available_qty', '>', 0)
            ->where('qc_status', 'QC Approved')
            ->get();

        return view('sales.dispatch.create', compact('orders', 'customers', 'selectedOrder', 'availableLots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sales_order_id' => 'required|exists:sales_orders,id',
            'customer_id' => 'required|exists:customers,id',
            'dispatch_date' => 'required|date',
            'transporter_name' => 'nullable|string|max:100',
            'vehicle_number' => 'nullable|string|max:50',
            'lr_number' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sales_order_item_id' => 'required|exists:sales_order_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.stock_lot_id' => 'required|exists:stock_lots,id',
            'items.*.dispatched_qty' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
        ]);

        $dispatchNumber = $this->docService->generate('DISPATCH');

        try {
            $dispatch = DB::transaction(function () use ($validated, $dispatchNumber) {
                $order = SalesOrder::findOrFail($validated['sales_order_id']);

                $dispatchRecord = Dispatch::create([
                    'dispatch_number' => $dispatchNumber,
                    'sales_order_id' => $order->id,
                    'customer_id' => $validated['customer_id'],
                    'dispatch_date' => $validated['dispatch_date'],
                    'transporter_name' => $validated['transporter_name'] ?? null,
                    'vehicle_number' => $validated['vehicle_number'] ?? null,
                    'lr_number' => $validated['lr_number'] ?? null,
                    'status' => 'Dispatched',
                    'remarks' => $validated['remarks'] ?? null,
                    'dispatched_by' => Auth::id(),
                ]);

                $allFulfilled = true;

                foreach ($validated['items'] as $item) {
                    $orderItem = SalesOrderItem::findOrFail($item['sales_order_item_id']);

                    // StockService dispatches from stock lot (Validates available stock and QC approval - Validation 20)
                    $this->stockService->dispatch(
                        $item['stock_lot_id'],
                        $item['dispatched_qty'],
                        $dispatchNumber,
                        $dispatchRecord->id
                    );

                    DispatchItem::create([
                        'dispatch_id' => $dispatchRecord->id,
                        'sales_order_item_id' => $orderItem->id,
                        'product_id' => $item['product_id'],
                        'stock_lot_id' => $item['stock_lot_id'],
                        'dispatched_qty' => $item['dispatched_qty'],
                        'uom_id' => $item['uom_id'],
                    ]);

                    $orderItem->dispatched_qty += $item['dispatched_qty'];
                    $orderItem->save();

                    if ($orderItem->dispatched_qty < $orderItem->quantity) {
                        $allFulfilled = false;
                    }
                }

                $order->status = $allFulfilled ? 'Complete' : 'Dispatched';
                $order->save();

                AuditService::log('sales', $dispatchRecord->dispatch_number, 'dispatch_stock', null, $dispatchRecord->toArray(), 'Stock dispatched for sales order');
                return $dispatchRecord;
            });

            return redirect()->route('sales.dispatch.show', $dispatch->id)
                ->with('success', "Dispatch Note {$dispatch->dispatch_number} posted and stock deducted from FG.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Dispatch failed: ' . $e->getMessage());
        }
    }

    public function show(Dispatch $dispatch)
    {
        $dispatch->load(['customer', 'salesOrder', 'items.product', 'items.stockLot.warehouse', 'items.uom']);
        return view('sales.dispatch.show', compact('dispatch'));
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
