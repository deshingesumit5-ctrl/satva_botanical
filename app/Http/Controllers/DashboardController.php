<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Grn;
use App\Models\QcRequest;
use App\Models\SalesOrder;
use App\Models\StockLot;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->query('customer_id');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $customers = Customer::where('status', 'Active')->orderBy('name')->get();

        // Order Status counts
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
            'in_progress' => $allOrders->whereIn('status', ['Approved', 'In Progress', 'Dispatched'])->count(),
            'delivered' => $allOrders->where('status', 'Delivered')->count(),
            'complete' => $allOrders->where('status', 'Complete')->count(),
            'cancelled' => $allOrders->where('status', 'Cancelled')->count(),
        ];

        // Stock Status counts
        $activeLots = StockLot::where('status', 'Active')->get();
        $stockStatus = [
            'low_stock' => $activeLots->where('available_qty', '<=', 10)->count(),
            'expired' => StockLot::where('expiry_date', '<', now())->where('available_qty', '>', 0)->count(),
        ];

        // GRN Status counts
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
            'pending_qc' => $grns->where('status', 'Pending QC')->count(),
            'completed' => $grns->where('status', 'Completed')->count(),
        ];

        // Warehouse Occupancy
        $warehouses = Warehouse::with(['storageLocations'])->where('status', 'Active')->get();
        $warehouseData = [];
        foreach ($warehouses as $wh) {
            $lotCount = StockLot::where('warehouse_id', $wh->id)->where('available_qty', '>', 0)->count();
            $warehouseData[] = [
                'name' => $wh->name,
                'lots' => $lotCount,
            ];
        }

        // Recent Batches
        $recentBatches = Batch::with(['product', 'productionPlan'])->latest()->take(6)->get();

        // QC Pending count
        $pendingQcCount = QcRequest::where('status', 'Pending')->count();
        $activeBatchCount = Batch::whereIn('status', ['In Progress', 'On Hold'])->count();

        return view('dashboard.index', compact(
            'customers',
            'orderStatus',
            'stockStatus',
            'grnStatus',
            'warehouseData',
            'recentBatches',
            'pendingQcCount',
            'activeBatchCount',
            'customerId',
            'fromDate',
            'toDate'
        ));
    }
}
