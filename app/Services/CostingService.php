<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BatchCost;
use App\Models\MonthlyExpense;
use App\Models\ExpenseAllocation;
use App\Models\ExpenseAllocationItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CostingService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Calculate direct material cost for a batch from actual consumption records.
     */
    public function calculateDirectMaterialCost(Batch $batch): float
    {
        $consumptions = $batch->consumptions()->with('stockLot')->get();
        $totalCost = 0.0;

        foreach ($consumptions as $c) {
            $lot = $c->stockLot;
            $unitCost = $lot ? $this->stockService->getLotValuationCost($lot->id) : 0.0;
            $netQty = (float)$c->net_consumed_qty > 0 ? (float)$c->net_consumed_qty : ((float)$c->actual_consumed_qty - (float)$c->returned_qty);
            $totalCost += ($netQty * $unitCost);
        }

        return round($totalCost, 2);
    }

    /**
     * Allocate monthly indirect expenses across eligible completed batches (Validation 23).
     * Method: Equal allocation per eligible completed batch.
     */
    public function allocateMonthlyExpenses(int $month, int $year, string $category, int $userId): ExpenseAllocation
    {
        // Validation 23: Duplicate monthly allocation blocked
        $existing = ExpenseAllocation::where('month', $month)
            ->where('year', $year)
            ->where('expense_category', $category)
            ->where('status', 'Posted')
            ->first();

        if ($existing) {
            throw new RuntimeException("Validation Error (23): Duplicate monthly allocation blocked. {$category} expenses for {$month}/{$year} are already allocated.");
        }

        return DB::transaction(function () use ($month, $year, $category, $userId) {
            $totalExpense = MonthlyExpense::where('month', $month)
                ->where('year', $year)
                ->where('expense_category', $category)
                ->where('status', 'Approved')
                ->sum('amount');

            if ($totalExpense <= 0) {
                throw new RuntimeException("No approved {$category} expenses found for {$month}/{$year}.");
            }

            // Find eligible completed batches in this month/year
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $batches = Batch::where('status', 'Completed')
                ->whereBetween('production_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get();

            $batchCount = $batches->count();
            if ($batchCount === 0) {
                throw new RuntimeException("No completed batches found for {$month}/{$year} to allocate expenses to.");
            }

            $perBatchAmount = round($totalExpense / $batchCount, 2);

            $allocation = ExpenseAllocation::create([
                'month' => $month,
                'year' => $year,
                'expense_category' => $category,
                'total_expense' => $totalExpense,
                'eligible_batch_count' => $batchCount,
                'allocation_method' => 'Equal per completed batch',
                'allocated_amount' => $totalExpense,
                'allocation_date' => now()->toDateString(),
                'posted_by' => $userId,
                'status' => 'Posted',
            ]);

            foreach ($batches as $index => $b) {
                $batchAllocAmount = ($index === $batchCount - 1)
                    ? round($totalExpense - ($perBatchAmount * ($batchCount - 1)), 2)
                    : $perBatchAmount;

                ExpenseAllocationItem::create([
                    'expense_allocation_id' => $allocation->id,
                    'batch_id' => $b->id,
                    'allocated_amount' => $batchAllocAmount,
                ]);

                // Update BatchCost summary
                $this->updateBatchCostSummary($b);
            }

            // Mark monthly expenses as Posted
            MonthlyExpense::where('month', $month)
                ->where('year', $year)
                ->where('expense_category', $category)
                ->where('status', 'Approved')
                ->update(['status' => 'Posted', 'posted_at' => now()]);

            AuditService::log('Costing', 'Expense Allocation', "Month: {$month}/{$year}, Category: {$category}", null, [
                'total_expense' => $totalExpense,
                'eligible_batches' => $batchCount,
                'per_batch' => $perBatchAmount,
            ], 'Allocated monthly manufacturing expenses');

            return $allocation;
        });
    }

    /**
     * Compute and store the full batch cost summary.
     */
    public function updateBatchCostSummary(Batch $batch): BatchCost
    {
        $directMatCost = $this->calculateDirectMaterialCost($batch);

        // Sum allocated expenses by category
        $allocItems = ExpenseAllocationItem::where('batch_id', $batch->id)
            ->with('allocation')
            ->get();

        $labourCost = 0.0;
        $electricityCost = 0.0;
        $maintenanceCost = 0.0;
        $equipmentCost = 0.0;
        $miscCost = 0.0;
        $otherCost = 0.0;

        foreach ($allocItems as $item) {
            $cat = $item->allocation->expense_category;
            $amt = (float)$item->allocated_amount;
            if ($cat === 'Labour') $labourCost += $amt;
            elseif ($cat === 'Electricity') $electricityCost += $amt;
            elseif ($cat === 'Maintenance') $maintenanceCost += $amt;
            elseif ($cat === 'Equipment-related') $equipmentCost += $amt;
            elseif ($cat === 'Miscellaneous') $miscCost += $amt;
            else $otherCost += $amt;
        }

        $totalCost = round($directMatCost + $labourCost + $electricityCost + $maintenanceCost + $equipmentCost + $miscCost + $otherCost, 2);
        $fgQty = (float)$batch->actual_qty > 0 ? (float)$batch->actual_qty : (float)$batch->planned_qty;
        $costPerUnit = $fgQty > 0 ? round($totalCost / $fgQty, 4) : 0.0;

        return BatchCost::updateOrCreate(
            ['batch_id' => $batch->id],
            [
                'direct_material_cost' => $directMatCost,
                'labour_cost' => $labourCost,
                'electricity_cost' => $electricityCost,
                'maintenance_cost' => $maintenanceCost,
                'equipment_cost' => $equipmentCost,
                'misc_cost' => $miscCost,
                'other_cost' => $otherCost,
                'total_batch_cost' => $totalCost,
                'finished_qty' => $fgQty,
                'cost_per_unit' => $costPerUnit,
                'calculated_at' => now(),
            ]
        );
    }
}
