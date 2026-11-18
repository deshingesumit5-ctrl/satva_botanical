<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\BatchCost;
use App\Models\BatchStageOutput;
use App\Models\Customer;
use App\Models\Grn;
use App\Models\MonthlyExpense;
use App\Models\ProductionPlan;
use App\Models\PurchaseOrder;
use App\Models\QcRequest;
use App\Models\RecoveryLot;
use App\Models\ReworkRecord;
use App\Models\SalesOrder;
use App\Models\StockLot;
use App\Models\Warehouse;
use App\Models\WasteRecord;
use App\Models\WipRecord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->query('customer_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $customers = Customer::where('status', 'Active')->orderBy('name')->get();

        // Order Status counts (aligned with sales_orders.status enum)
        $orderQuery = SalesOrder::query();
        if ($customerId) {
            $orderQuery->where('customer_id', $customerId);
        }
        if ($fromDate) {
            $orderQuery->whereDate('order_date', '>=', $fromDate);
        }
        if ($toDate) {
            $orderQuery->whereDate('order_date', '<=', $toDate);
        }

        $allOrders = $orderQuery->get();
        $orderStatus = [
            'draft' => $allOrders->where('status', 'Draft')->count(),
            'in_progress' => $allOrders->whereIn('status', ['Confirmed', 'In Progress', 'Approved'])->count(),
            'delivered' => $allOrders->whereIn('status', ['Partially Dispatched', 'Fully Dispatched', 'Delivered'])->count(),
            'complete' => $allOrders->whereIn('status', ['Invoiced', 'Complete'])->count(),
            'cancelled' => $allOrders->where('status', 'Cancelled')->count(),
            'total' => $allOrders->count(),
        ];

        // Stock Status counts
        $activeLots = StockLot::where('status', 'Active')->get();
        $stockStatus = [
            'low_stock' => $activeLots->where('available_qty', '>', 0)->where('available_qty', '<=', 10)->count(),
            'expired' => StockLot::where('expiry_date', '<', now())->where('available_qty', '>', 0)->count(),
            'total_lots' => StockLot::count(),
        ];

        // GRN Status counts (aligned with grns.status enum: Draft, Received, Completed, Cancelled)
        $grnQuery = Grn::query();
        if ($fromDate) {
            $grnQuery->whereDate('grn_date', '>=', $fromDate);
        }
        if ($toDate) {
            $grnQuery->whereDate('grn_date', '<=', $toDate);
        }
        $grns = $grnQuery->get();
        $grnStatus = [
            'draft' => $grns->where('status', 'Draft')->count(),
            'pending_qc' => $grns->whereIn('status', ['Received', 'Pending QC'])->count(),
            'completed' => $grns->where('status', 'Completed')->count(),
            'total' => $grns->count(),
        ];

        // Warehouse Occupancy
        $warehouses = Warehouse::where('status', 'Active')->get();
        $warehouseData = [];
        $totalWarehouseLots = 0;
        foreach ($warehouses as $wh) {
            $lotCount = StockLot::where('warehouse_id', $wh->id)->where('available_qty', '>', 0)->count();
            $totalWarehouseLots += $lotCount;
            $warehouseData[] = [
                'name' => $wh->name,
                'lots' => $lotCount,
            ];
        }

        // Recent Batches
        $batchQuery = Batch::with(['product', 'productionPlan', 'uom']);
        if ($fromDate) {
            $batchQuery->whereDate('production_date', '>=', $fromDate);
        }
        if ($toDate) {
            $batchQuery->whereDate('production_date', '<=', $toDate);
        }
        $recentBatches = $batchQuery->latest()->take(6)->get();

        // Management Dashboard (Section 32.1) — 18 Real Live Metrics (zero hardcoded/dummy data)
        $planQuery = ProductionPlan::query();
        if ($fromDate) {
            $planQuery->whereDate('planned_start_date', '>=', $fromDate);
        }
        if ($toDate) {
            $planQuery->whereDate('planned_start_date', '<=', $toDate);
        }
        $totalProductionPlans = $planQuery->count();

        $allBatchesQuery = Batch::query();
        if ($fromDate) {
            $allBatchesQuery->whereDate('production_date', '>=', $fromDate);
        }
        if ($toDate) {
            $allBatchesQuery->whereDate('production_date', '<=', $toDate);
        }
        $allBatches = $allBatchesQuery->get();

        $totalBatches = $allBatches->count();
        $runningBatches = $allBatches->where('status', 'In Progress')->count();
        $completedBatches = $allBatches->where('status', 'Completed')->count();
        $onHoldBatches = $allBatches->where('status', 'On Hold')->count();
        $rejectedBatches = $allBatches->where('status', 'Rejected')->count();
        $totalProductionQty = (float) $allBatches->sum('actual_qty');

        $pendingQcCount = QcRequest::where('status', 'Pending')->count();
        $reworkRequiredCount = ReworkRecord::where('status', '!=', 'Completed')->count()
            + $allBatches->where('status', 'Rework Required')->count();

        $finishedGoodsStock = (float) StockLot::whereNotNull('product_id')
            ->where('status', 'Active')
            ->sum('available_qty');

        $rawMaterialStock = (float) StockLot::whereNotNull('material_id')
            ->where('status', 'Active')
            ->sum('available_qty');

        $wipStock = (float) WipRecord::where('available_qty', '>', 0)->sum('available_qty');
        $recoveredSolventStock = (float) RecoveryLot::where('available_qty', '>', 0)->sum('available_qty');
        $saleableByProductStock = (float) BatchStageOutput::where('output_type', 'By-product')->sum('actual_qty');
        $wasteSpentQty = (float) WasteRecord::sum('quantity');

        $purchasePendingCount = PurchaseOrder::whereIn('status', ['Draft', 'Pending Approval', 'Approved', 'Partially Received'])->count();
        $salesPendingCount = $allOrders->whereIn('status', ['Draft', 'Confirmed', 'In Progress', 'Partially Dispatched'])->count();

        $batchCostQuery = BatchCost::query();
        if ($fromDate) {
            $batchCostQuery->whereDate('calculated_at', '>=', $fromDate);
        }
        if ($toDate) {
            $batchCostQuery->whereDate('calculated_at', '<=', $toDate);
        }
        if (!$fromDate && !$toDate) {
            $batchCostQuery->whereMonth('calculated_at', now()->month)
                ->whereYear('calculated_at', now()->year);
        }
        $monthlyBatchCost = (float) $batchCostQuery->sum('total_batch_cost');

        $expenseQuery = MonthlyExpense::query();
        if (!$fromDate && !$toDate) {
            $expenseQuery->where('month', now()->month)->where('year', now()->year);
        } else {
            if ($fromDate) {
                $expenseQuery->whereDate('entry_date', '>=', $fromDate);
            }
            if ($toDate) {
                $expenseQuery->whereDate('entry_date', '<=', $toDate);
            }
        }
        $monthlyExpenses = (float) $expenseQuery->sum('amount');
        $monthlyProductionCost = $monthlyBatchCost > 0 ? $monthlyBatchCost : $monthlyExpenses;

        $activeBatchCount = $runningBatches + $onHoldBatches;

        $managementStats = [
            [
                'label' => 'Total Production Plans',
                'value' => $totalProductionPlans,
                'display' => $totalProductionPlans > 0 ? number_format($totalProductionPlans) : null,
                'icon' => 'bi-calendar2-check',
                'color' => 'primary',
                'route' => route('production.plans.index'),
            ],
            [
                'label' => 'Total Batches',
                'value' => $totalBatches,
                'display' => $totalBatches > 0 ? number_format($totalBatches) : null,
                'icon' => 'bi-collection-fill',
                'color' => 'dark',
                'route' => route('production.batches.index'),
            ],
            [
                'label' => 'Running Batches',
                'value' => $runningBatches,
                'display' => $runningBatches > 0 ? number_format($runningBatches) : null,
                'icon' => 'bi-play-circle-fill',
                'color' => 'success',
                'route' => route('production.batches.index', ['status' => 'In Progress']),
            ],
            [
                'label' => 'Completed Batches',
                'value' => $completedBatches,
                'display' => $completedBatches > 0 ? number_format($completedBatches) : null,
                'icon' => 'bi-check-circle-fill',
                'color' => 'success',
                'route' => route('production.batches.index', ['status' => 'Completed']),
            ],
            [
                'label' => 'On-Hold Batches',
                'value' => $onHoldBatches,
                'display' => $onHoldBatches > 0 ? number_format($onHoldBatches) : null,
                'icon' => 'bi-pause-circle-fill',
                'color' => 'warning',
                'route' => route('production.batches.index', ['status' => 'On Hold']),
            ],
            [
                'label' => 'QC Pending',
                'value' => $pendingQcCount,
                'display' => $pendingQcCount > 0 ? number_format($pendingQcCount) : null,
                'icon' => 'bi-hourglass-split',
                'color' => 'warning',
                'route' => route('qc.pending'),
            ],
            [
                'label' => 'Rework Required',
                'value' => $reworkRequiredCount,
                'display' => $reworkRequiredCount > 0 ? number_format($reworkRequiredCount) : null,
                'icon' => 'bi-arrow-repeat',
                'color' => 'info',
                'route' => route('production.rework.index'),
            ],
            [
                'label' => 'Rejected Batches',
                'value' => $rejectedBatches,
                'display' => $rejectedBatches > 0 ? number_format($rejectedBatches) : null,
                'icon' => 'bi-x-octagon-fill',
                'color' => 'danger',
                'route' => route('production.batches.index', ['status' => 'Rejected']),
            ],
            [
                'label' => 'Total Production Quantity',
                'value' => $totalProductionQty,
                'display' => $totalProductionQty > 0 ? number_format($totalProductionQty, 2) . ' KG' : null,
                'icon' => 'bi-speedometer2',
                'color' => 'primary',
                'route' => route('reports.production'),
            ],
            [
                'label' => 'Finished Goods Stock',
                'value' => $finishedGoodsStock,
                'display' => $finishedGoodsStock > 0 ? number_format($finishedGoodsStock, 2) . ' KG' : null,
                'icon' => 'bi-box-seam-fill',
                'color' => 'success',
                'route' => route('inventory.stock.lots'),
            ],
            [
                'label' => 'Raw Material Stock',
                'value' => $rawMaterialStock,
                'display' => $rawMaterialStock > 0 ? number_format($rawMaterialStock, 2) . ' KG' : null,
                'icon' => 'bi-boxes',
                'color' => 'primary',
                'route' => route('inventory.stock.index'),
            ],
            [
                'label' => 'WIP Stock',
                'value' => $wipStock,
                'display' => $wipStock > 0 ? number_format($wipStock, 2) . ' KG' : null,
                'icon' => 'bi-layers-half',
                'color' => 'info',
                'route' => route('inventory.wip.index'),
            ],
            [
                'label' => 'Recovered Solvent Stock',
                'value' => $recoveredSolventStock,
                'display' => $recoveredSolventStock > 0 ? number_format($recoveredSolventStock, 2) . ' LTR' : null,
                'icon' => 'bi-recycle',
                'color' => 'success',
                'route' => route('inventory.recovery.index'),
            ],
            [
                'label' => 'Saleable By-product Stock',
                'value' => $saleableByProductStock,
                'display' => $saleableByProductStock > 0 ? number_format($saleableByProductStock, 2) . ' KG' : null,
                'icon' => 'bi-bag-check',
                'color' => 'info',
                'route' => route('inventory.waste.index'),
            ],
            [
                'label' => 'Waste / Spent Quantity',
                'value' => $wasteSpentQty,
                'display' => $wasteSpentQty > 0 ? number_format($wasteSpentQty, 2) . ' KG' : null,
                'icon' => 'bi-trash3',
                'color' => 'secondary',
                'route' => route('inventory.waste.index'),
            ],
            [
                'label' => 'Purchase Pending',
                'value' => $purchasePendingCount,
                'display' => $purchasePendingCount > 0 ? number_format($purchasePendingCount) : null,
                'icon' => 'bi-cart-plus',
                'color' => 'warning',
                'route' => route('purchase.orders.index'),
            ],
            [
                'label' => 'Sales Pending',
                'value' => $salesPendingCount,
                'display' => $salesPendingCount > 0 ? number_format($salesPendingCount) : null,
                'icon' => 'bi-receipt',
                'color' => 'primary',
                'route' => route('sales.orders.index'),
            ],
            [
                'label' => 'Monthly Production Cost',
                'value' => $monthlyProductionCost,
                'display' => $monthlyProductionCost > 0 ? '₹' . number_format($monthlyProductionCost, 2) : null,
                'icon' => 'bi-currency-rupee',
                'color' => 'danger',
                'route' => route('costing.batch-cost.index'),
            ],
        ];

        return response()
            ->view('dashboard.index', compact(
                'customers',
                'orderStatus',
                'stockStatus',
                'grnStatus',
                'warehouseData',
                'totalWarehouseLots',
                'recentBatches',
                'pendingQcCount',
                'activeBatchCount',
                'managementStats',
                'customerId',
                'fromDate',
                'toDate'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
