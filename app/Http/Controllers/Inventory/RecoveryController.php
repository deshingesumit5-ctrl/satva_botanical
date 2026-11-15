<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\RecoveryLot;
use App\Models\Stage;
use App\Services\RecoveryService;
use Illuminate\Http\Request;

class RecoveryController extends Controller
{
    protected RecoveryService $recoveryService;

    public function __construct(RecoveryService $recoveryService)
    {
        $this->recoveryService = $recoveryService;
    }

    public function index(Request $request)
    {
        $query = RecoveryLot::with(['sourceBatch.product', 'stage', 'warehouse', 'consumptions.consumingBatch']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recovery_lot_number', 'like', "%{$search}%")
                  ->orWhere('solvent_name', 'like', "%{$search}%")
                  ->orWhereHas('sourceBatch', function ($bq) use ($search) {
                      $bq->where('batch_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $recoveryLots = $query->latest()->paginate(15)->withQueryString();
        $activeBatches = Batch::whereIn('status', ['In Progress', 'Created'])->get();
        $stages = Stage::where('status', 'Active')->get();

        return view('inventory.recovery.index', compact('recoveryLots', 'activeBatches', 'stages'));
    }

    public function consume(Request $request, RecoveryLot $lot)
    {
        $validated = $request->validate([
            'consuming_batch_id' => 'required|exists:batches,id',
            'consuming_stage_id' => 'nullable|exists:stages,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        try {
            $this->recoveryService->consume(
                $lot->id,
                $validated['quantity'],
                $validated['consuming_batch_id'],
                $validated['consuming_stage_id'] ?? null
            );

            return redirect()->route('inventory.recovery.index')
                ->with('success', "Consumed {$validated['quantity']} L of recovered solvent from Lot {$lot->recovery_lot_number}.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
