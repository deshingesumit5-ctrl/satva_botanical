<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\BatchCost;
use App\Services\CostingService;
use Illuminate\Http\Request;

class BatchCostController extends Controller
{
    protected CostingService $costingService;

    public function __construct(CostingService $costingService)
    {
        $this->costingService = $costingService;
    }

    public function index(Request $request)
    {
        $query = BatchCost::with(['batch.product', 'batch.recipeVersion']);

        if ($search = $request->input('search')) {
            $query->whereHas('batch', function ($bq) use ($search) {
                $bq->where('batch_no', 'like', "%{$search}%")
                   ->orWhereHas('product', function ($pq) use ($search) {
                       $pq->where('name', 'like', "%{$search}%");
                   });
            });
        }

        $costs = $query->latest()->paginate(15)->withQueryString();

        return view('costing.batch-cost.index', compact('costs'));
    }

    public function show(Batch $batch)
    {
        $batch->load([
            'product',
            'recipeVersion',
            'materialConsumptions.stockLot.material',
            'reconciliation',
            'cost',
        ]);

        // Auto-calculate / update if not yet finalized
        $cost = $this->costingService->updateBatchCostSummary($batch);

        return view('costing.batch-cost.show', compact('batch', 'cost'));
    }
}
