<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchReconciliation;
use App\Models\FinishedGoodsReceipt;
use App\Models\StockLot;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\ProductionService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReconciliationController extends Controller
{
    protected ProductionService $productionService;
    protected DocumentNumberService $docService;
    protected StockService $stockService;

    public function __construct(
        ProductionService $productionService,
        DocumentNumberService $docService,
        StockService $stockService
    ) {
        $this->productionService = $productionService;
        $this->docService = $docService;
        $this->stockService = $stockService;
    }

    public function show(Batch $batch)
    {
        $batch->load([
            'product',
            'recipeVersion',
            'materialConsumptions.stockLot.material',
            'stageExecutions.outputs',
            'reconciliation',
            'qcRequests',
        ]);

        // Auto-calculate suggested input totals from recorded material consumptions
        $consumptions = $batch->materialConsumptions;
        $rawQty = 0;
        $chemQty = 0;
        $solvQty = 0;
        $waterQty = 0;
        $otherInputQty = 0;

        foreach ($consumptions as $c) {
            $cat = $c->stockLot && $c->stockLot->material ? $c->stockLot->material->material_category : 'Raw';
            if ($cat === 'Raw') $rawQty += (float)$c->consumed_qty;
            elseif ($cat === 'Chemical') $chemQty += (float)$c->consumed_qty;
            elseif ($cat === 'Solvent') $solvQty += (float)$c->consumed_qty;
            elseif ($cat === 'Water') $waterQty += (float)$c->consumed_qty;
            else $otherInputQty += (float)$c->consumed_qty;
        }

        // Auto-calculate suggested output totals from recorded stage outputs
        $outputs = $batch->stageExecutions->flatMap->outputs;
        $fgQty = 0;
        $wipQty = 0;
        $recSolvQty = 0;
        $byProductQty = 0;
        $spentQty = 0;
        $wasteQty = 0;

        foreach ($outputs as $out) {
            $type = $out->output_type;
            if ($type === 'Finished') $fgQty += (float)$out->actual_qty;
            elseif ($type === 'WIP') $wipQty += (float)$out->actual_qty;
            elseif ($type === 'Recovered') $recSolvQty += (float)$out->actual_qty;
            elseif ($type === 'By-product') $byProductQty += (float)$out->actual_qty;
            elseif ($type === 'Spent') $spentQty += (float)$out->actual_qty;
            elseif ($type === 'Waste') $wasteQty += (float)$out->actual_qty;
        }

        $warehouses = Warehouse::where('status', 'Active')->where('type', 'Finished Goods')->get();
        $validationErrors = $this->productionService->validateBatchClosure($batch);

        return view('production.reconciliation.show', compact(
            'batch',
            'rawQty',
            'chemQty',
            'solvQty',
            'waterQty',
            'otherInputQty',
            'fgQty',
            'wipQty',
            'recSolvQty',
            'byProductQty',
            'spentQty',
            'wasteQty',
            'warehouses',
            'validationErrors'
        ));
    }

    public function save(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'raw_material_qty' => 'nullable|numeric|min:0',
            'water_qty' => 'nullable|numeric|min:0',
            'chemical_qty' => 'nullable|numeric|min:0',
            'solvent_qty' => 'nullable|numeric|min:0',
            'other_input_qty' => 'nullable|numeric|min:0',
            'fg_qty' => 'required|numeric|min:0.001',
            'wip_qty' => 'nullable|numeric|min:0',
            'cake_qty' => 'nullable|numeric|min:0',
            'liquid_qty' => 'nullable|numeric|min:0',
            'recovered_solvent_qty' => 'nullable|numeric|min:0',
            'by_product_qty' => 'nullable|numeric|min:0',
            'spent_qty' => 'nullable|numeric|min:0',
            'waste_qty' => 'nullable|numeric|min:0',
            'material_returns_qty' => 'nullable|numeric|min:0',
            'destination_warehouse_id' => 'required|exists:warehouses,id',
            'remarks' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($batch, $validated) {
                // Close batch and create BatchReconciliation
                $recon = $this->productionService->closeBatch($batch, $validated, Auth::id());

                // Create Finished Goods Stock Lot & Inward Ledger
                $fgrNumber = $this->docService->generate('FGR');
                $fgLotNumber = $batch->batch_no; // Finished Good lot carries batch number

                $stockLot = $this->stockService->inward([
                    'lot_number' => $fgLotNumber,
                    'product_id' => $batch->product_id,
                    'warehouse_id' => $validated['destination_warehouse_id'],
                    'quantity' => $validated['fg_qty'],
                    'available_qty' => $validated['fg_qty'],
                    'unit_cost' => 0, // updated later during cost allocation
                    'qc_status' => 'QC Approved',
                    'mfg_date' => now()->toDateString(),
                    'expiry_date' => $batch->product->shelf_life ? now()->addDays($batch->product->shelf_life)->toDateString() : null,
                    'txn_type' => 'Production Inward',
                    'reference_type' => 'Batch',
                    'reference_id' => $batch->id,
                    'reference_no' => $batch->batch_no,
                    'remarks' => "Finished Goods Receipt for Batch {$batch->batch_no}",
                ]);

                FinishedGoodsReceipt::create([
                    'fgr_number' => $fgrNumber,
                    'batch_id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'warehouse_id' => $validated['destination_warehouse_id'],
                    'stock_lot_id' => $stockLot->id,
                    'quantity' => $validated['fg_qty'],
                    'uom_id' => $batch->uom_id,
                    'receipt_date' => now(),
                    'status' => 'Received',
                    'received_by' => Auth::id(),
                    'remarks' => $validated['remarks'] ?? null,
                ]);

                AuditService::log('production', $batch->batch_no, 'reconciliation_and_close', null, $recon->toArray(), 'Reconciled and closed batch');
            });

            return redirect()->route('production.batches.show', $batch->id)
                ->with('success', "Batch {$batch->batch_no} reconciled and closed successfully! Finished Goods Receipt generated.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Closure failed: ' . $e->getMessage());
        }
    }

    public function close(Request $request, Batch $batch)
    {
        return $this->save($request, $batch);
    }
}
