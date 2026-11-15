<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchActivityExecution;
use App\Models\BatchParameterReading;
use App\Models\BatchStageExecution;
use App\Models\BatchStageOutput;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Product;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionExecutionController extends Controller
{
    protected ProductionService $productionService;

    public function __construct(ProductionService $productionService)
    {
        $this->productionService = $productionService;
    }

    public function show(Batch $batch, Request $request)
    {
        $batch->load([
            'product',
            'recipe',
            'recipeVersion',
            'stageExecutions.stage',
            'stageExecutions.equipment',
            'stageExecutions.activities.recipeStageActivity.activity',
            'stageExecutions.parameters.recipeParameter.parameter',
            'stageExecutions.outputs.material',
            'stageExecutions.outputs.product',
            'stageExecutions.outputs.uom',
            'materialAllocations.stockLot.material',
            'materialConsumptions.stockLot',
        ]);

        $stageExecutions = $batch->stageExecutions()->orderBy('sequence_no')->orderBy('execution_number')->get();

        // Find active stage execution or requested execution
        $activeExecutionId = $request->input('stage_execution_id');
        $activeExecution = null;

        if ($activeExecutionId) {
            $activeExecution = $stageExecutions->firstWhere('id', $activeExecutionId);
        }

        if (!$activeExecution) {
            $activeExecution = $stageExecutions->firstWhere('status', 'In Progress')
                ?? $stageExecutions->firstWhere('status', 'QC Pending')
                ?? $stageExecutions->firstWhere('status', 'Pending')
                ?? $stageExecutions->last();
        }

        $equipments = Equipment::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->orderBy('name')->get();
        $products = Product::where('status', 'Active')->orderBy('name')->get();

        return view('production.execution.show', compact(
            'batch',
            'stageExecutions',
            'activeExecution',
            'equipments',
            'warehouses',
            'uoms',
            'materials',
            'products'
        ));
    }

    public function startStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $stageExecution->update([
            'status' => 'In Progress',
            'start_time' => now(),
        ]);
        return back()->with('success', "Stage '{$stageExecution->stage_name}' started.");
    }

    public function saveStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        // 1. Update Equipment & Clearance
        if ($request->has('equipment_id')) {
            $stageExecution->equipment_id = $request->input('equipment_id');
            $stageExecution->actual_operator_name = $request->input('actual_operator_name', $stageExecution->actual_operator_name);
            $stageExecution->operator_count = $request->input('operator_count', $stageExecution->operator_count);
            $stageExecution->save();
        }

        // 2. Update Activities (timings)
        $activities = $request->input('activities', []);
        foreach ($activities as $actId => $data) {
            $act = BatchActivityExecution::find($actId);
            if ($act && $act->batch_stage_execution_id === $stageExecution->id) {
                $act->start_datetime = !empty($data['start_datetime']) ? $data['start_datetime'] : null;
                $act->end_datetime = !empty($data['end_datetime']) ? $data['end_datetime'] : null;
                $act->operator_name = $data['operator_name'] ?? null;
                $act->remarks = $data['remarks'] ?? null;
                if ($act->start_datetime && $act->end_datetime) {
                    $act->status = 'Completed';
                }
                $act->save();
            }
        }

        // 3. Update Parameters (readings)
        $parameters = $request->input('parameters', []);
        foreach ($parameters as $paramId => $data) {
            $reading = BatchParameterReading::find($paramId);
            if ($reading && $reading->batch_stage_execution_id === $stageExecution->id) {
                $reading->actual_value = $data['actual_value'] ?? null;
                $reading->reading_time = now();
                $reading->recorded_by = Auth::id();

                // Range check
                if ($reading->actual_value !== null && is_numeric($reading->actual_value)) {
                    $val = (float)$reading->actual_value;
                    $min = $reading->min_value !== null ? (float)$reading->min_value : null;
                    $max = $reading->max_value !== null ? (float)$reading->max_value : null;

                    $within = true;
                    if ($min !== null && $val < $min) {
                        $within = false;
                    }
                    if ($max !== null && $val > $max) {
                        $within = false;
                    }
                    $reading->is_within_range = $within;
                }
                $reading->save();
            }
        }

        AuditService::log('production', $batch->batch_no, 'save_stage_execution', null, null, "Saved progress for stage {$stageExecution->stage_name}");

        return redirect()->route('production.batches.execute', ['batch' => $batch->id, 'stage_execution_id' => $stageExecution->id])
            ->with('success', 'Stage progress saved.');
    }

    public function completeStage(Request $request, Batch $batch, BatchStageExecution $stageExecution)
    {
        $outputs = $request->input('outputs', []);
        $remarks = $request->input('supervisor_remarks');

        try {
            $this->productionService->completeStage($stageExecution, $outputs, $remarks);

            return redirect()->route('production.batches.execute', ['batch' => $batch->id])
                ->with('success', "Stage '{$stageExecution->stage_name}' completed successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
