<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchMaterialAllocation;
use App\Models\BatchMaterialConsumption;
use App\Models\BatchMaterialRequirement;
use App\Models\ProductionPlan;
use App\Models\StockLot;
use App\Services\AuditService;
use App\Services\ProductionService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialRequirementController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function show(ProductionPlan $plan)
    {
        $plan->load(['product', 'recipe.currentVersion.stages.inputMaterials.material.baseUom']);
        return view('production.plans.requirements', compact('plan'));
    }

    public function allocate(Batch $batch)
    {
        if ($batch->materialRequirements()->count() === 0 && $batch->recipeVersion) {
            app(ProductionService::class)->generateMaterialRequirements($batch);
        }

        $batch->load([
            'product',
            'uom',
            'materialRequirements.material.baseUom',
            'materialRequirements.uom',
            'materialRequirements.recipeStage.stage',
            'materialAllocations.stockLot.warehouse',
        ]);

        // Get available active stock lots for required materials (FIFO)
        $requiredMaterialIds = $batch->materialRequirements->pluck('material_id')->unique();
        $availableLots = StockLot::with(['warehouse', 'storageLocation', 'material.baseUom'])
            ->whereIn('material_id', $requiredMaterialIds)
            ->where('status', 'Active')
            ->where('available_qty', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
            })
            ->where('qc_status', '!=', 'QC Rejected')
            ->where('qc_status', '!=', 'QC Hold')
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC')
            ->orderBy('id')
            ->get()
            ->groupBy('material_id');

        return view('production.batches.allocate', compact('batch', 'availableLots'));
    }

    public function saveAllocation(Request $request, Batch $batch)
    {
        $allocations = $request->input('allocations', []);
        $batchRef = $batch->batch_number ?? $batch->batch_no ?? ('BATCH-' . $batch->id);

        try {
            $allocatedCount = 0;

            DB::transaction(function () use ($batch, $allocations, $batchRef, &$allocatedCount) {
                foreach ($allocations as $key => $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }

                    // Support both flat row format (from allocate.blade.php) and nested multi-lot rows
                    $isFlatRow = array_key_exists('stock_lot_id', $entry) || array_key_exists('allocated_qty', $entry) || array_key_exists('quantity', $entry);
                    $rows = $isFlatRow ? [$entry] : $entry;
                    $defaultReqId = $entry['requirement_id'] ?? $key;

                    foreach ($rows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }

                        $lotId = $row['stock_lot_id'] ?? null;
                        $qty = (float) ($row['allocated_qty'] ?? $row['quantity'] ?? 0);

                        if (empty($lotId) || $qty <= 0) {
                            continue;
                        }

                        $reqId = $row['requirement_id'] ?? $defaultReqId;
                        $requirement = BatchMaterialRequirement::where('batch_id', $batch->id)->find($reqId);

                        if (!$requirement && !empty($row['material_id'])) {
                            $requirement = BatchMaterialRequirement::where('batch_id', $batch->id)
                                ->where('material_id', $row['material_id'])
                                ->first();
                        }

                        if (!$requirement) {
                            throw new \RuntimeException('Matching batch material requirement not found.');
                        }

                        // StockService validates stock availability, qc status, expiry date
                        $this->stockService->allocate((int) $lotId, $qty, $batchRef, $batch->id);

                        BatchMaterialAllocation::create([
                            'batch_id'        => $batch->id,
                            'recipe_stage_id' => $requirement->recipe_stage_id,
                            'material_id'     => $requirement->material_id,
                            'stock_lot_id'    => (int) $lotId,
                            'uom_id'          => $requirement->uom_id,
                            'allocated_qty'   => $qty,
                            'allocated_at'    => now(),
                            'allocated_by'    => Auth::id(),
                        ]);

                        $requirement->allocated_qty = (float) $requirement->allocated_qty + $qty;
                        if ($requirement->allocated_qty >= (float) $requirement->planned_qty) {
                            $requirement->status = 'Fully Allocated';
                        } elseif ($requirement->allocated_qty > 0) {
                            $requirement->status = 'Partially Allocated';
                        }
                        $requirement->save();

                        $allocatedCount++;
                    }
                }

                if ($allocatedCount > 0) {
                    AuditService::log('production', 'material_allocation', $batchRef, null, null, 'Allocated material lots for batch');
                }
            });

            if ($allocatedCount === 0) {
                return back()->withInput()->with('error', 'Please select a stock lot and enter an allocation quantity greater than 0.');
            }

            return redirect()->route('production.batches.show', $batch->id)
                ->with('success', 'Material lots allocated successfully.');
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Allocation failed: ' . $e->getMessage());
        }
    }

    public function consume(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'allocation_id' => 'required|exists:batch_material_allocations,id',
            'quantity' => 'required|numeric|min:0.001',
            'stage_execution_id' => 'nullable|exists:batch_stage_executions,id',
        ]);

        $allocation = BatchMaterialAllocation::findOrFail($validated['allocation_id']);

        try {
            DB::transaction(function () use ($batch, $allocation, $validated) {
                // Consume from reserved stock
                $this->stockService->consume($allocation->stock_lot_id, $validated['quantity'], $batch->batch_no, $batch->id, true);

                BatchMaterialConsumption::create([
                    'batch_id' => $batch->id,
                    'batch_material_requirement_id' => $allocation->batch_material_requirement_id,
                    'batch_stage_execution_id' => $validated['stage_execution_id'] ?? null,
                    'material_id' => $allocation->material_id,
                    'stock_lot_id' => $allocation->stock_lot_id,
                    'consumed_qty' => $validated['quantity'],
                    'consumed_date' => now(),
                    'consumed_by' => Auth::id(),
                ]);

                $allocation->consumed_qty += $validated['quantity'];
                if ($allocation->consumed_qty >= $allocation->allocated_qty) {
                    $allocation->status = 'Consumed';
                }
                $allocation->save();
            });

            return redirect()->back()->with('success', "Consumed {$validated['quantity']} units into batch execution.");
        } catch (\Exception $e) {
            return back()->with('error', 'Consumption failed: ' . $e->getMessage());
        }
    }

    public function returnMaterial(Request $request, Batch $batch)
    {
        $validated = $request->validate([
            'allocation_id' => 'required|exists:batch_material_allocations,id',
            'return_qty' => 'required|numeric|min:0.001',
            'reason' => 'required|string|max:255',
        ]);

        $allocation = BatchMaterialAllocation::findOrFail($validated['allocation_id']);
        $unconsumed = (float)$allocation->allocated_qty - (float)$allocation->consumed_qty;

        if ((float)$validated['return_qty'] > $unconsumed) {
            return back()->with('error', "Cannot return more than unconsumed allocated quantity ({$unconsumed}).");
        }

        try {
            DB::transaction(function () use ($batch, $allocation, $validated) {
                // Unreserve from lot and return to available
                $lot = StockLot::findOrFail($allocation->stock_lot_id);
                $lot->reserved_qty = max(0, (float)$lot->reserved_qty - (float)$validated['return_qty']);
                $lot->available_qty = (float)$lot->available_qty + (float)$validated['return_qty'];
                $lot->returned_qty = (float)$lot->returned_qty + (float)$validated['return_qty'];
                $lot->save();

                $allocation->allocated_qty -= $validated['return_qty'];
                $allocation->save();

                AuditService::log('production', $batch->batch_no, 'return_material', null, [
                    'lot' => $lot->lot_number,
                    'return_qty' => $validated['return_qty'],
                    'reason' => $validated['reason'],
                ], 'Returned unused material');
            });

            return redirect()->back()->with('success', "Returned {$validated['return_qty']} units to Lot.");
        } catch (\Exception $e) {
            return back()->with('error', 'Return failed: ' . $e->getMessage());
        }
    }
}
