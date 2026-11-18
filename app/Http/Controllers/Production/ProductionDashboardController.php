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
        $onHoldCount = Batch::where('status', 'On Hold')->count();
        $completedBatchesCount = Batch::where('status', 'Completed')->count();
        $plansCount = ProductionPlan::whereIn('status', ['Approved', 'In Progress'])->count();
        $pendingQcCount = QcRequest::where('status', 'Pending')->count();
        $qcPendingBatchesCount = Batch::where(function ($q) {
            $q->where('status', 'QC Pending')
                ->orWhereHas('stageExecutions', fn ($sq) => $sq->where('status', 'QC Pending'));
        })->count();
        $reworkCount = ReworkRecord::where('status', 'In Progress')->count();

        $batches = Batch::with(['product', 'recipe', 'stageExecutions.qcRequest'])
            ->latest()
            ->take(10)
            ->get();
        $recentBatches = $batches;

        $plans = ProductionPlan::with(['product', 'warehouse'])
            ->latest()
            ->take(5)
            ->get();

        return response()
            ->view('production.dashboard', compact(
                'activeBatchesCount',
                'onHoldCount',
                'completedBatchesCount',
                'plansCount',
                'pendingQcCount',
                'qcPendingBatchesCount',
                'reworkCount',
                'batches',
                'recentBatches',
                'plans'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
