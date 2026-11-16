<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Material;
use App\Models\Plant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\Supplier;
use App\Models\Uom;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['supplier', 'plant', 'items.material']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();

        return view('purchase.orders.index', compact('orders', 'suppliers'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();
        $plants = Plant::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->with('baseUom')->orderBy('name')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        $fromPr = null;
        if ($prId = $request->input('pr_id')) {
            $fromPr = PurchaseRequisition::with('items.material')->find($prId);
        }

        return view('purchase.orders.create', compact('suppliers', 'plants', 'materials', 'uoms', 'fromPr'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'plant_id' => 'nullable|exists:plants,id',
            'pr_id' => 'nullable|exists:purchase_requisitions,id',
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'order_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'nullable|numeric|min:0.001',
            'items.*.ordered_qty' => 'nullable|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $poNumber = $this->docService->generate('PO');

        $po = DB::transaction(function () use ($validated, $poNumber) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? $item['ordered_qty'] ?? 0);
                $lineSub = round($qty * (float) $item['unit_price'], 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $grandTotal = $subtotal + $taxTotal;
            $prId = $validated['pr_id'] ?? $validated['purchase_requisition_id'] ?? null;
            $poDate = $validated['order_date'] ?? now()->toDateString();
            $expectedDelivery = $validated['expected_delivery_date'] ?? $validated['delivery_date'] ?? null;
            $terms = $validated['delivery_terms'] ?? $validated['notes'] ?? null;
            $plant = !empty($validated['plant_id']) ? Plant::find($validated['plant_id']) : null;
            $deliveryAddress = $validated['shipping_address'] ?? ($plant ? $plant->name : null);

            $cgst = round($taxTotal / 2, 2);
            $sgst = round($taxTotal - $cgst, 2);

            $order = PurchaseOrder::create([
                'po_number' => $poNumber,
                'purchase_requisition_id' => $prId,
                'supplier_id' => $validated['supplier_id'],
                'po_date' => $poDate,
                'expected_delivery_date' => $expectedDelivery,
                'delivery_address' => $deliveryAddress,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'terms_conditions' => $terms,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'taxable_amount' => $subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => 0,
                'total_amount' => $grandTotal,
                'status' => 'Draft',
            ]);

            foreach ($validated['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? $item['ordered_qty'] ?? 0);
                $rate = (float) $item['unit_price'];
                $lineSub = round($qty * $rate, 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $qty,
                    'received_qty' => 0,
                    'uom_id' => $item['uom_id'],
                    'rate' => $rate,
                    'discount_percent' => 0,
                    'taxable_amount' => $lineSub,
                    'gst_rate' => $taxRate,
                    'gst_amount' => $lineTax,
                    'total_amount' => $lineSub + $lineTax,
                ]);
            }

            AuditService::log('purchase', $order->po_number, 'create_po', null, $order->toArray(), 'Created purchase order');
            return $order;
        });

        return redirect()->route('purchase.orders.show', $po->id)->with('success', "Purchase Order {$po->po_number} created.");
    }

    public function show(PurchaseOrder $order)
    {
        $order->load(['supplier', 'plant', 'items.material', 'items.uom', 'grns']);
        return view('purchase.orders.show', compact('order'));
    }

    public function edit(PurchaseOrder $order)
    {
        if ($order->status !== 'Draft') {
            return redirect()->route('purchase.orders.show', $order->id)->with('warning', 'Only Draft purchase orders can be edited.');
        }

        $order->load('items');
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();
        $plants = Plant::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->with('baseUom')->orderBy('name')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('purchase.orders.edit', compact('order', 'suppliers', 'plants', 'materials', 'uoms'));
    }

    public function update(Request $request, PurchaseOrder $order)
    {
        if ($order->status !== 'Draft') {
            return back()->with('error', 'Only Draft orders can be updated.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'plant_id' => 'nullable|exists:plants,id',
            'order_date' => 'nullable|date',
            'expected_delivery_date' => 'nullable|date',
            'delivery_date' => 'nullable|date',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'nullable|numeric|min:0.001',
            'items.*.ordered_qty' => 'nullable|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($order, $validated) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? $item['ordered_qty'] ?? 0);
                $lineSub = round($qty * (float) $item['unit_price'], 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $expectedDelivery = $validated['expected_delivery_date'] ?? $validated['delivery_date'] ?? $order->expected_delivery_date;
            $terms = $validated['delivery_terms'] ?? $validated['notes'] ?? null;
            $plant = !empty($validated['plant_id']) ? Plant::find($validated['plant_id']) : null;
            $cgst = round($taxTotal / 2, 2);
            $sgst = round($taxTotal - $cgst, 2);

            $order->update([
                'supplier_id' => $validated['supplier_id'],
                'po_date' => $validated['order_date'] ?? $order->po_date ?? now()->toDateString(),
                'expected_delivery_date' => $expectedDelivery,
                'delivery_address' => $plant ? $plant->name : $order->delivery_address,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'terms_conditions' => $terms,
                'subtotal' => $subtotal,
                'taxable_amount' => $subtotal,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'total_amount' => $subtotal + $taxTotal,
            ]);

            $order->items()->delete();
            foreach ($validated['items'] as $item) {
                $qty = (float) ($item['quantity'] ?? $item['ordered_qty'] ?? 0);
                $rate = (float) $item['unit_price'];
                $lineSub = round($qty * $rate, 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $qty,
                    'received_qty' => 0,
                    'uom_id' => $item['uom_id'],
                    'rate' => $rate,
                    'discount_percent' => 0,
                    'taxable_amount' => $lineSub,
                    'gst_rate' => $taxRate,
                    'gst_amount' => $lineTax,
                    'total_amount' => $lineSub + $lineTax,
                ]);
            }

            AuditService::log('purchase', $order->po_number, 'update_po', null, null, 'Updated purchase order');
        });

        return redirect()->route('purchase.orders.show', $order->id)->with('success', 'Purchase Order updated.');
    }

    public function destroy(PurchaseOrder $order)
    {
        if ($order->status !== 'Draft') {
            return back()->with('error', 'Only Draft orders can be deleted.');
        }

        try {
            $code = $order->po_number;
            $order->items()->delete();
            $order->delete();

            AuditService::log('purchase', $code, 'delete_po', null, null, 'Deleted purchase order');

            return redirect()->route('purchase.orders.index')->with('success', 'Purchase Order deleted.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'This record is already used and cannot be deleted. You can deactivate it.');
        }
    }

    public function approve(PurchaseOrder $order)
    {
        if ($order->status !== 'Draft') {
            return back()->with('error', 'Only Draft orders can be approved.');
        }

        $order->status = 'Approved';
        $order->approved_by = Auth::id();
        $order->approved_at = now();
        $order->save();

        AuditService::log('purchase', $order->po_number, 'approve_po', null, null, 'Approved purchase order');

        return redirect()->route('purchase.orders.show', $order->id)->with('success', "Purchase Order {$order->po_number} approved and released for receipt.");
    }

    public function cancel(Request $request, PurchaseOrder $order)
    {
        if ($order->status === 'Completed' || $order->status === 'Cancelled') {
            return back()->with('error', 'Completed or already cancelled orders cannot be cancelled.');
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $order->status = 'Cancelled';
        $order->remarks = ($order->remarks ? $order->remarks . "\n" : '') . "Cancellation reason: " . $request->input('reason');
        $order->save();

        AuditService::log('purchase', $order->po_number, 'cancel_po', null, ['reason' => $request->input('reason')], 'Cancelled PO');

        return redirect()->route('purchase.orders.show', $order->id)->with('success', 'Purchase Order cancelled.');
    }

    public function print(PurchaseOrder $order)
    {
        $order->load(['supplier', 'plant', 'items.material', 'items.uom']);
        $company = CompanySetting::first();

        $pdf = Pdf::loadView('purchase.orders.print', compact('order', 'company'));
        return $pdf->download("PO_{$order->po_number}.pdf");
    }
}
