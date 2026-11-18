<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Uom;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'items.product']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('so_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        return view('sales.orders.index', compact('orders'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'Active')->orderBy('name')->get();
        $products = Product::where('status', 'Active')->where('is_saleable', true)->orderBy('name')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('sales.orders.create', compact('customers', 'products', 'uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'payment_terms' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $soNumber = $this->docService->generate('SO');

        $order = DB::transaction(function () use ($validated, $soNumber) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $lineSub = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $cgst = round($taxTotal / 2, 2);
            $sgst = round($taxTotal - $cgst, 2);

            $salesOrder = SalesOrder::create([
                'so_number' => $soNumber,
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'taxable_amount' => $subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => 0,
                'total_amount' => $subtotal + $taxTotal,
                'status' => 'Draft',
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $rate = (float) $item['unit_price'];
                $qty = (float) $item['quantity'];
                $lineSub = round($qty * $rate, 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'quantity' => $qty,
                    'dispatched_qty' => 0,
                    'uom_id' => $item['uom_id'],
                    'rate' => $rate,
                    'discount_percent' => 0,
                    'taxable_amount' => $lineSub,
                    'gst_rate' => $taxRate,
                    'gst_amount' => $lineTax,
                    'total_amount' => $lineSub + $lineTax,
                ]);
            }

            AuditService::log('sales', $salesOrder->so_number, 'create_sales_order', null, $salesOrder->toArray(), 'Created sales order');
            return $salesOrder;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Sales Order {$order->so_number} created.",
                'redirect' => route('sales.orders.index'),
            ]);
        }

        return redirect()->route('sales.orders.show', $order->id)->with('success', "Sales Order {$order->so_number} created.");
    }

    public function show(SalesOrder $order)
    {
        $order->load(['customer', 'items.product.baseUom', 'items.uom', 'dispatches']);
        return view('sales.orders.show', compact('order'));
    }

    public function edit(SalesOrder $order)
    {
        if ($order->status !== 'Draft') {
            return redirect()->route('sales.orders.show', $order->id)->with('warning', 'Only Draft sales orders can be edited.');
        }

        $order->load('items');
        $customers = Customer::where('status', 'Active')->orderBy('name')->get();
        $products = Product::where('status', 'Active')->where('is_saleable', true)->orderBy('name')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('sales.orders.edit', compact('order', 'customers', 'products', 'uoms'));
    }

    public function update(Request $request, SalesOrder $order)
    {
        if ($order->status !== 'Draft') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Only Draft orders can be updated.'], 422);
            }
            return back()->with('error', 'Only Draft orders can be updated.');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'payment_terms' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($order, $validated) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $lineSub = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $cgst = round($taxTotal / 2, 2);
            $sgst = round($taxTotal - $cgst, 2);

            $order->update([
                'customer_id' => $validated['customer_id'],
                'order_date' => $validated['order_date'],
                'delivery_date' => $validated['delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'subtotal' => $subtotal,
                'taxable_amount' => $subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => 0,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            $order->items()->delete();
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $rate = (float) $item['unit_price'];
                $qty = (float) $item['quantity'];
                $lineSub = round($qty * $rate, 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'quantity' => $qty,
                    'dispatched_qty' => 0,
                    'uom_id' => $item['uom_id'],
                    'rate' => $rate,
                    'discount_percent' => 0,
                    'taxable_amount' => $lineSub,
                    'gst_rate' => $taxRate,
                    'gst_amount' => $lineTax,
                    'total_amount' => $lineSub + $lineTax,
                ]);
            }

            AuditService::log('sales', $order->so_number, 'update_sales_order', null, null, 'Updated sales order');
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Sales Order {$order->so_number} updated.",
                'redirect' => route('sales.orders.index'),
            ]);
        }

        return redirect()->route('sales.orders.show', $order->id)->with('success', 'Sales Order updated.');
    }

    public function destroy(SalesOrder $order)
    {
        if ($order->status !== 'Draft') {
            return back()->with('error', 'Only Draft orders can be deleted.');
        }

        try {
            $num = $order->so_number;
            $order->items()->delete();
            $order->delete();

            AuditService::log('sales', $num, 'delete_sales_order', null, null, 'Deleted sales order');

            return redirect()->route('sales.orders.index')->with('success', 'Sales Order deleted.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'This record is already used and cannot be deleted. You can deactivate it.');
        }
    }

    public function approve(SalesOrder $order)
    {
        if ($order->status !== 'Draft') {
            return back()->with('error', 'Only Draft orders can be confirmed.');
        }

        $order->status = 'Confirmed';
        $order->save();

        AuditService::log('sales', $order->so_number, 'confirm_sales_order', null, null, 'Confirmed sales order');

        return redirect()->route('sales.orders.show', $order->id)->with('success', "Sales Order {$order->so_number} confirmed and released for dispatch.");
    }

    public function cancel(Request $request, SalesOrder $order)
    {
        if (in_array($order->status, ['Fully Dispatched', 'Invoiced', 'Cancelled'])) {
            return back()->with('error', 'Fulfilled, invoiced, or already cancelled orders cannot be cancelled.');
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $order->status = 'Cancelled';
        $order->save();

        AuditService::log('sales', $order->so_number, 'cancel_sales_order', null, ['reason' => $request->input('reason')], 'Cancelled sales order');

        return redirect()->route('sales.orders.show', $order->id)->with('success', 'Sales Order cancelled.');
    }
}
