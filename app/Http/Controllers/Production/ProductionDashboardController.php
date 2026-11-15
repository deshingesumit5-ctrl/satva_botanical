<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\ProductionPlan;
use App\Models\QcRequest;
use App\Models\ReworkRecord;
use Illuminate\Http\Request;

class ProductionDashboardController extends Controller
{
    public function index()
    {
        $activeBatchesCount = Batch::whereIn('status', ['In Progress', 'On Hold'])->count();
        $plansCount = ProductionPlan::whereIn('status', ['Approved', 'In Progress'])->count();
        $pendingQcCount = QcRequest::where('status', 'Pending')->count();
        $reworkCount = ReworkRecord::where('status', 'In Progress')->count();

        $batches = Batch::with(['product', 'recipe', 'stageExecutions'])
            ->latest()
            ->take(10)
            ->get();
        $recentBatches = $batches;

        $plans = ProductionPlan::with(['product', 'warehouse'])
            ->latest()
            ->take(5)
            ->get();

        return view('production.dashboard', compact(
            'activeBatchesCount',
            'plansCount',
            'pendingQcCount',
            'reworkCount',
            'batches',
            'recentBatches',
            'plans'
        ));
    }
}
