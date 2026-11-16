<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\Material;
use App\Models\Plant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\QcRequest;
use App\Models\StorageLocation;
use App\Models\Supplier;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GrnController extends Controller
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
        $query = Grn::with(['supplier', 'plant', 'warehouse', 'purchaseOrder', 'items.material']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('grn_number', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $grns = $query->latest()->paginate(10)->withQueryString();

        return view('purchase.grn.index', compact('grns'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();
        $plants = Plant::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->whereIn('type', ['Raw Material', 'Chemical'])->get();
        $locations = StorageLocation::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->orderBy('name')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();
        $approvedPos = PurchaseOrder::where('status', 'Approved')->with('items.material')->get();

        return view('purchase.grn.create', compact('suppliers', 'plants', 'warehouses', 'locations', 'materials', 'uoms', 'approvedPos'));
    }

    public function createFromPo(PurchaseOrder $order)
    {
        $order->load(['supplier', 'plant', 'items.material.baseUom', 'items.uom']);
        $warehouses = Warehouse::where('status', 'Active')->whereIn('type', ['Raw Material', 'Chemical'])->get();
        $locations = StorageLocation::where('status', 'Active')->get();

        return view('purchase.grn.from-po', compact('order', 'warehouses', 'locations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'plant_id' => 'nullable|exists:plants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'grn_date' => 'nullable|date',
            'receipt_date' => 'nullable|date',
            'invoice_number' => 'nullable|string|max:50',
            'invoice_date' => 'nullable|date',
            'vehicle_number' => 'nullable|string|max:30',
            'gate_pass_number' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.received_qty' => 'nullable|numeric|min:0.001',
            'items.*.received_quantity' => 'nullable|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.storage_location_id' => 'nullable|exists:storage_locations,id',
            'items.*.supplier_lot_number' => 'nullable|string|max:50',
            'items.*.vendor_lot_number' => 'nullable|string|max:50',
            'items.*.mfg_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date|after_or_equal:items.*.mfg_date',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.po_item_id' => 'nullable|exists:purchase_order_items,id',
        ]);

        $grnNumber = $this->docService->generate('GRN');

        $grn = DB::transaction(function () use ($validated, $grnNumber) {
            $hasQcPending = false;
            $poId = $validated['purchase_order_id'] ?? null;
            if (!$poId && !empty($validated['items'][0]['po_item_id'])) {
                $firstPoItem = PurchaseOrderItem::find($validated['items'][0]['po_item_id']);
                $poId = $firstPoItem?->purchase_order_id;
            }
            $po = $poId ? PurchaseOrder::find($poId) : null;
            $supplierId = $validated['supplier_id'] ?? $po?->supplier_id;
            $receiptDate = $validated['grn_date'] ?? $validated['receipt_date'] ?? now()->toDateString();

            $remarksParts = array_filter([
                $validated['remarks'] ?? null,
                !empty($validated['vehicle_number']) ? "Vehicle: {$validated['vehicle_number']}" : null,
                !empty($validated['gate_pass_number']) ? "Gate Pass: {$validated['gate_pass_number']}" : null,
            ]);

            $grnRecord = Grn::create([
                'grn_number' => $grnNumber,
                'purchase_order_id' => $poId,
                'supplier_id' => $supplierId,
                'receipt_date' => $receiptDate,
                'warehouse_id' => $validated['warehouse_id'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'] ?? $receiptDate,
                'qc_status' => 'Pending',
                'status' => 'Received',
                'remarks' => $remarksParts ? implode(' | ', $remarksParts) : null,
                'received_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $receivedQty = (float) ($item['received_qty'] ?? $item['received_quantity'] ?? 0);
                if ($receivedQty <= 0) {
                    continue;
                }

                $material = Material::findOrFail($item['material_id']);
                $requiresQc = (bool) $material->qc_required;
                if ($requiresQc) {
                    $hasQcPending = true;
                }

                $qcStatus = $requiresQc ? 'QC Hold' : 'QC Approved';
                $availableQty = $requiresQc ? 0 : $receivedQty;

                $lotNumber = $this->docService->generate('LOT');
                $poItemId = $item['purchase_order_item_id'] ?? $item['po_item_id'] ?? null;
                $poItem = $poItemId ? PurchaseOrderItem::find($poItemId) : null;
                $unitCost = (float) ($item['unit_cost'] ?? $poItem?->rate ?? $material->standard_cost ?? 0);
                $vendorLot = $item['supplier_lot_number'] ?? $item['vendor_lot_number'] ?? null;

                $grnItem = GrnItem::create([
                    'grn_id' => $grnRecord->id,
                    'purchase_order_item_id' => $poItemId,
                    'material_id' => $material->id,
                    'po_qty' => $poItem?->quantity ?? 0,
                    'received_qty' => $receivedQty,
                    'accepted_qty' => $requiresQc ? 0 : $receivedQty,
                    'rejected_qty' => 0,
                    'shortage_excess' => 0,
                    'lot_number' => $lotNumber,
                    'mfg_date' => $item['mfg_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'warehouse_id' => $validated['warehouse_id'],
                    'qc_required' => $requiresQc,
                    'qc_status' => $qcStatus,
                    'rate' => $unitCost,
                    'remarks' => $vendorLot,
                ]);

                // Create Stock Lot & Ledger Entry
                $this->stockService->inward([
                    'lot_number' => $lotNumber,
                    'material_id' => $material->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'storage_location_id' => $item['storage_location_id'] ?? null,
                    'supplier_id' => $supplierId,
                    'grn_id' => $grnRecord->id,
                    'grn_item_id' => $grnItem->id,
                    'quantity' => $receivedQty,
                    'available_qty' => $availableQty,
                    'unit_cost' => $unitCost,
                    'qc_status' => $qcStatus,
                    'mfg_date' => $item['mfg_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'transaction_type' => 'GRN Inward',
                    'reference_type' => 'GRN',
                    'reference_id' => $grnRecord->id,
                    'reference_no' => $grnRecord->grn_number,
                    'remarks' => "GRN: {$grnRecord->grn_number}",
                ]);

                // Update PO Item received qty if linked
                if ($poItem) {
                    $poItem->received_qty = (float) $poItem->received_qty + $receivedQty;
                    $poItem->save();
                }
            }

            if ($hasQcPending) {
                $grnRecord->qc_status = 'QC Hold';
                $grnRecord->save();
            } else {
                $grnRecord->qc_status = 'QC Approved';
                $grnRecord->status = 'Completed';
                $grnRecord->save();
            }

            if ($po) {
                $po->load('items');
                $allReceived = $po->items->every(fn ($i) => (float) $i->received_qty >= (float) $i->quantity);
                $po->status = $allReceived ? 'Fully Received' : 'Partially Received';
                $po->save();
            }

            AuditService::log('purchase', $grnRecord->grn_number, 'create_grn', null, $grnRecord->toArray(), 'Goods receipt recorded');
            return $grnRecord;
        });

        return redirect()->route('purchase.grn.show', $grn->id)
            ->with('success', "GRN {$grn->grn_number} created with stock lots recorded.");
    }

    public function show(Grn $grn)
    {
        $grn->load(['supplier', 'plant', 'warehouse', 'purchaseOrder', 'items.material', 'items.uom', 'items.storageLocation', 'stockLots']);
        return view('purchase.grn.show', compact('grn'));
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
