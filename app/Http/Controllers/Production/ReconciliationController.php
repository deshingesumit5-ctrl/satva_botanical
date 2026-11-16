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
            'recipeVersion.stages.inputMaterials.material',
            'recipeVersion.stages.outputMaterials',
            'materialConsumptions.material',
            'materialConsumptions.stockLot.material',
            'stageExecutions.outputs',
            'reconciliation',
            'qcRequests',
        ]);

        // Auto-calculate suggested input totals from recorded material consumptions & recipe stage inputs
        $consumptions = $batch->materialConsumptions;
        $rawQty = 0;
        $chemQty = 0;
        $solvQty = 0;
        $waterQty = 0;
        $otherInputQty = 0;

        $countedStageMatKeys = [];
        foreach ($consumptions as $c) {
            $mat = $c->material ?? ($c->stockLot ? $c->stockLot->material : null);
            $cat = $mat ? $mat->material_category : 'Raw';
            $qty = (float) ($c->actual_consumed_qty ?? $c->net_consumed_qty ?? $c->allocated_qty ?? $c->consumed_qty ?? 0);

            if ($cat === 'Raw')
                $rawQty += $qty;
            elseif ($cat === 'Chemical')
                $chemQty += $qty;
            elseif ($cat === 'Solvent')
                $solvQty += $qty;
            elseif ($cat === 'Water')
                $waterQty += $qty;
            else
                $otherInputQty += $qty;

            if ($c->recipe_stage_id && $c->material_id) {
                $countedStageMatKeys[$c->recipe_stage_id . '_' . $c->material_id] = true;
            }
        }

        // Also include recipe input materials that were not lot-tracked (e.g. Demineralized Water)
        if ($batch->recipeVersion && $batch->recipeVersion->stages) {
            foreach ($batch->recipeVersion->stages as $rStage) {
                foreach ($rStage->inputMaterials as $inMat) {
                    $key = $rStage->id . '_' . ($inMat->material_id ?? 0);
                    if (empty($countedStageMatKeys[$key])) {
                        $cat = $inMat->material ? $inMat->material->material_category : ($inMat->material_category ?? 'Raw');
                        $qty = (float) $inMat->standard_qty;
                        if ($cat === 'Raw')
                            $rawQty += $qty;
                        elseif ($cat === 'Chemical')
                            $chemQty += $qty;
                        elseif ($cat === 'Solvent')
                            $solvQty += $qty;
                        elseif ($cat === 'Water')
                            $waterQty += $qty;
                        else
                            $otherInputQty += $qty;
                    }
                }
            }
        }

        // Auto-calculate suggested output totals from recorded stage outputs
        $outputs = $batch->stageExecutions->flatMap->outputs;
        $fgQty = 0;
        $wipQty = 0;
        $recSolvQty = 0;
        $byProductQty = 0;
        $spentQty = 0;
        $wasteQty = 0;

        $maxSeq = (int) $batch->stageExecutions->max('sequence_no');
        foreach ($batch->stageExecutions as $se) {
            foreach ($se->outputs as $out) {
                $type = $out->output_type;
                if ($type === 'Finished' || ($se->sequence_no === $maxSeq && $type === 'WIP')) {
                    $fgQty += (float) $out->actual_qty;
                } elseif ($type === 'WIP') {
                    $wipQty += (float) $out->actual_qty;
                } elseif ($type === 'Recovered') {
                    $recSolvQty += (float) $out->actual_qty;
                } elseif ($type === 'By-product') {
                    $byProductQty += (float) $out->actual_qty;
                } elseif ($type === 'Spent') {
                    $spentQty += (float) $out->actual_qty;
                } elseif ($type === 'Waste') {
                    $wasteQty += (float) $out->actual_qty;
                }
            }
        }

        if ($fgQty <= 0) {
            $fgQty = (float) ($batch->actual_qty > 0 ? $batch->actual_qty : ($batch->planned_qty ?? 100));
        }

        $warehouses = Warehouse::where('status', 'Active')->get();
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
        if (!$request->has('destination_warehouse_id') && $request->has('fg_warehouse_id')) {
            $request->merge(['destination_warehouse_id' => $request->input('fg_warehouse_id')]);
        }
        if (!$request->has('fg_qty') || (float) $request->input('fg_qty') <= 0) {
            $fallbackFg = (float) ($batch->actual_qty > 0 ? $batch->actual_qty : ($batch->planned_qty ?: 100));
            $request->merge(['fg_qty' => $fallbackFg]);
        }

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
                $fgLotNumber = $batch->batch_number ?? $batch->batch_no ?? ('LOT-FG-' . $batch->id);
                $mfgDate = now()->toDateString();
                $expiryDate = ($batch->product && $batch->product->shelf_life)
                    ? now()->addDays($batch->product->shelf_life)->toDateString()
                    : now()->addYears(2)->toDateString();

                $stockLot = $this->stockService->inward([
                    'lot_number' => $fgLotNumber,
                    'product_id' => $batch->product_id,
                    'warehouse_id' => $validated['destination_warehouse_id'],
                    'quantity' => $validated['fg_qty'],
                    'available_qty' => $validated['fg_qty'],
                    'unit_cost' => 0,
                    'qc_status' => 'QC Approved',
                    'mfg_date' => $mfgDate,
                    'expiry_date' => $expiryDate,
                    'transaction_type' => 'Production Inward',
                    'reference_type' => 'Batch',
                    'reference_id' => $batch->id,
                    'reference_no' => $fgLotNumber,
                    'remarks' => "Finished Goods Receipt for Batch {$fgLotNumber}",
                ]);

                FinishedGoodsReceipt::create([
                    'receipt_number' => $fgrNumber,
                    'batch_id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'fg_lot_number' => $fgLotNumber,
                    'actual_qty' => $validated['fg_qty'],
                    'uom_id' => $batch->uom_id,
                    'mfg_date' => $mfgDate,
                    'expiry_date' => $expiryDate,
                    'warehouse_id' => $validated['destination_warehouse_id'],
                    'qc_status' => 'QC Approved',
                    'received_by' => Auth::id() ?? 1,
                    'receipt_date' => $mfgDate,
                    'remarks' => $validated['remarks'] ?? "Finished Goods Receipt for Batch {$fgLotNumber}",
                ]);

                AuditService::log('production', $fgLotNumber, 'reconciliation_and_close', null, $recon->toArray(), 'Reconciled and closed batch');
            });

            return redirect()->route('production.batches.show', $batch->id)
                ->with('success', "Batch " . ($batch->batch_number ?? $batch->batch_no) . " reconciled and closed successfully! Finished Goods Receipt generated.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Closure failed: ' . $e->getMessage());
        }
    }

    public function close(Request $request, Batch $batch)
    {
        return $this->save($request, $batch);
    }
}
