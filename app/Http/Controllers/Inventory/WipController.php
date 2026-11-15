<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Stage;
use App\Models\WipRecord;
use App\Services\WipService;
use Illuminate\Http\Request;

class WipController extends Controller
{
    protected WipService $wipService;

    public function __construct(WipService $wipService)
    {
        $this->wipService = $wipService;
    }

    public function index(Request $request)
    {
        $query = WipRecord::with(['batch.product', 'stage', 'uom', 'warehouse', 'destinationStage']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('wip_number', 'like', "%{$search}%")
                  ->orWhereHas('batch', function ($bq) use ($search) {
                      $bq->where('batch_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $wipRecords = $query->latest()->paginate(15)->withQueryString();
        $activeBatches = Batch::whereIn('status', ['In Progress', 'Created'])->get();
        $stages = Stage::where('status', 'Active')->get();

        return view('inventory.wip.index', compact('wipRecords', 'activeBatches', 'stages'));
    }

    public function consume(Request $request, WipRecord $wip)
    {
        $validated = $request->validate([
            'consuming_batch_id' => 'required|exists:batches,id',
            'consuming_stage_id' => 'required|exists:stages,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        try {
            $this->wipService->consume(
                $wip->id,
                $validated['quantity'],
                $validated['consuming_batch_id'],
                $validated['consuming_stage_id']
            );

            return redirect()->route('inventory.wip.index')
                ->with('success', "WIP {$wip->wip_number} consumed {$validated['quantity']} units.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
