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
                  ->orWhere('vehicle_number', 'like', "%{$search}%")
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
            'supplier_id' => 'required|exists:suppliers,id',
            'plant_id' => 'required|exists:plants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'grn_date' => 'required|date',
            'invoice_number' => 'nullable|string|max:50',
            'invoice_date' => 'nullable|date',
            'vehicle_number' => 'nullable|string|max:30',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.received_qty' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.storage_location_id' => 'nullable|exists:storage_locations,id',
            'items.*.supplier_lot_number' => 'nullable|string|max:50',
            'items.*.mfg_date' => 'nullable|date',
            'items.*.expiry_date' => 'nullable|date|after_or_equal:items.*.mfg_date',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
        ]);

        $grnNumber = $this->docService->generate('GRN');

        $grn = DB::transaction(function () use ($validated, $grnNumber) {
            $hasQcPending = false;

            $grnRecord = Grn::create([
                'grn_number' => $grnNumber,
                'supplier_id' => $validated['supplier_id'],
                'plant_id' => $validated['plant_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'purchase_order_id' => $validated['purchase_order_id'] ?? null,
                'grn_date' => $validated['grn_date'],
                'invoice_number' => $validated['invoice_number'] ?? null,
                'invoice_date' => $validated['invoice_date'] ?? null,
                'vehicle_number' => $validated['vehicle_number'] ?? null,
                'status' => 'Pending QC',
                'remarks' => $validated['remarks'] ?? null,
                'received_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $material = Material::findOrFail($item['material_id']);
                $requiresQc = $material->qc_required;
                if ($requiresQc) {
                    $hasQcPending = true;
                }

                $qcStatus = $requiresQc ? 'Under Quarantine' : 'QC Approved';
                $availableQty = $requiresQc ? 0 : $item['received_qty'];

                $lotNumber = $this->docService->generate('LOT');

                $grnItem = GrnItem::create([
                    'grn_id' => $grnRecord->id,
                    'material_id' => $material->id,
                    'received_qty' => $item['received_qty'],
                    'accepted_qty' => $requiresQc ? 0 : $item['received_qty'],
                    'rejected_qty' => 0,
                    'uom_id' => $item['uom_id'],
                    'unit_price' => $item['unit_cost'] ?? 0,
                    'supplier_batch_no' => $item['supplier_lot_number'] ?? null,
                    'mfg_date' => $item['mfg_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'lot_number' => $lotNumber,
                    'storage_location_id' => $item['storage_location_id'] ?? null,
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                ]);

                // Create Stock Lot & Ledger Entry
                $stockLot = $this->stockService->inward([
                    'lot_number' => $lotNumber,
                    'material_id' => $material->id,
                    'warehouse_id' => $validated['warehouse_id'],
                    'storage_location_id' => $item['storage_location_id'] ?? null,
                    'supplier_id' => $validated['supplier_id'],
                    'grn_id' => $grnRecord->id,
                    'grn_item_id' => $grnItem->id,
                    'quantity' => $item['received_qty'],
                    'available_qty' => $availableQty,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'qc_status' => $qcStatus,
                    'mfg_date' => $item['mfg_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'txn_type' => 'GRN Inward',
                    'remarks' => "GRN: {$grnRecord->grn_number}",
                ]);

                // Trigger QC Request if required
                if ($requiresQc) {
                    $qcCode = $this->docService->generate('QC');
                    QcRequest::create([
                        'request_number' => $qcCode,
                        'stage_type' => 'incoming_material',
                        'grn_id' => $grnRecord->id,
                        'grn_item_id' => $grnItem->id,
                        'stock_lot_id' => $stockLot->id,
                        'material_id' => $material->id,
                        'sample_qty' => 1.0,
                        'uom_id' => $item['uom_id'],
                        'status' => 'Pending',
                        'remarks' => "Incoming Inspection for GRN {$grnRecord->grn_number}",
                        'requested_by' => Auth::id(),
                    ]);
                }

                // Update PO Item received qty if linked
                if (!empty($item['purchase_order_item_id'])) {
                    $poItem = PurchaseOrderItem::find($item['purchase_order_item_id']);
                    if ($poItem) {
                        $poItem->received_qty += $item['received_qty'];
                        $poItem->save();
                    }
                }
            }

            // If none required QC, mark GRN completed
            if (!$hasQcPending) {
                $grnRecord->status = 'Completed';
                $grnRecord->save();
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
