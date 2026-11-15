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
            'plant_id' => 'required|exists:plants,id',
            'purchase_requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'payment_terms' => 'nullable|string|max:100',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.ordered_qty' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $poNumber = $this->docService->generate('PO');

        $po = DB::transaction(function () use ($validated, $poNumber) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $lineSub = $item['ordered_qty'] * $item['unit_price'];
                $taxRate = $item['tax_percent'] ?? 0;
                $lineTax = $lineSub * ($taxRate / 100);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $grandTotal = $subtotal + $taxTotal;

            $order = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'plant_id' => $validated['plant_id'],
                'purchase_requisition_id' => $validated['purchase_requisition_id'] ?? null,
                'po_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'grand_total' => $grandTotal,
                'status' => 'Draft',
                'remarks' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $lineSub = $item['ordered_qty'] * $item['unit_price'];
                $taxRate = $item['tax_percent'] ?? 0;
                $lineTax = $lineSub * ($taxRate / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'material_id' => $item['material_id'],
                    'ordered_qty' => $item['ordered_qty'],
                    'received_qty' => 0,
                    'uom_id' => $item['uom_id'],
                    'unit_price' => $item['unit_price'],
                    'tax_percent' => $taxRate,
                    'line_total' => $lineSub + $lineTax,
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
            'plant_id' => 'required|exists:plants,id',
            'order_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:order_date',
            'payment_terms' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.ordered_qty' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::transaction(function () use ($order, $validated) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $lineSub = $item['ordered_qty'] * $item['unit_price'];
                $taxRate = $item['tax_percent'] ?? 0;
                $lineTax = $lineSub * ($taxRate / 100);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $order->update([
                'supplier_id' => $validated['supplier_id'],
                'plant_id' => $validated['plant_id'],
                'po_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['delivery_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'grand_total' => $subtotal + $taxTotal,
                'remarks' => $validated['notes'] ?? null,
            ]);

            $order->items()->delete();
            foreach ($validated['items'] as $item) {
                $lineSub = $item['ordered_qty'] * $item['unit_price'];
                $taxRate = $item['tax_percent'] ?? 0;
                $lineTax = $lineSub * ($taxRate / 100);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'material_id' => $item['material_id'],
                    'ordered_qty' => $item['ordered_qty'],
                    'received_qty' => 0,
                    'uom_id' => $item['uom_id'],
                    'unit_price' => $item['unit_price'],
                    'tax_percent' => $taxRate,
                    'line_total' => $lineSub + $lineTax,
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

        $code = $order->po_number;
        $order->items()->delete();
        $order->delete();

        AuditService::log('purchase', $code, 'delete_po', null, null, 'Deleted purchase order');

        return redirect()->route('purchase.orders.index')->with('success', 'Purchase Order deleted.');
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
