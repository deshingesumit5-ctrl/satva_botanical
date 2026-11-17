<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Material;
use App\Models\RecoveryLot;
use App\Models\Stage;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\RecoveryService;
use Illuminate\Http\Request;

class RecoveryController extends Controller
{
    protected RecoveryService $recoveryService;
    protected DocumentNumberService $docService;

    public function __construct(RecoveryService $recoveryService, DocumentNumberService $docService)
    {
        $this->recoveryService = $recoveryService;
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = RecoveryLot::with(['sourceBatch.product', 'stage', 'warehouse', 'material', 'consumptions.consumingBatch']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('recovery_lot_number', 'like', "%{$search}%")
                  ->orWhereHas('sourceBatch', function ($bq) use ($search) {
                      $bq->where('batch_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $recoveryLots = $query->latest()->paginate(15)->withQueryString();
        $activeBatches = Batch::latest()->get();
        $stages = Stage::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();

        return view('inventory.recovery.index', compact('recoveryLots', 'activeBatches', 'stages', 'materials', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_batch_id' => 'required|exists:batches,id',
            'material_id'     => 'required|exists:materials,id',
            'generated_qty'   => 'required|numeric|min:0.001',
            'warehouse_id'    => 'required|exists:warehouses,id',
            'recovery_date'   => 'required|date',
        ]);

        $lotNo = $request->input('recovery_lot_number') ?: ('REC-' . date('Ymd') . '-' . str_pad(RecoveryLot::count() + 1, 3, '0', STR_PAD_LEFT));

        $lot = RecoveryLot::create([
            'recovery_lot_number' => $lotNo,
            'source_batch_id'     => $validated['source_batch_id'],
            'source_stage_id'     => $request->input('source_stage_id'),
            'material_id'         => $validated['material_id'],
            'generated_qty'       => $validated['generated_qty'],
            'available_qty'       => $validated['generated_qty'],
            'reserved_qty'        => 0,
            'consumed_qty'        => 0,
            'sold_qty'            => 0,
            'remaining_qty'       => $validated['generated_qty'],
            'warehouse_id'        => $validated['warehouse_id'],
            'recovery_date'       => $validated['recovery_date'],
            'qc_status'           => 'Approved',
            'status'              => 'Active',
        ]);

        AuditService::log('inventory', $lot->recovery_lot_number, 'create_recovery_lot', null, $lot->toArray(), 'Recorded recovered solvent lot');

        return redirect()->route('inventory.recovery.index')->with('success', "Recovered solvent lot '{$lot->recovery_lot_number}' recorded successfully.");
    }

    public function consume(Request $request, RecoveryLot $lot)
    {
        $validated = $request->validate([
            'consuming_batch_id' => 'required|exists:batches,id',
            'consuming_stage_id' => 'nullable|exists:stages,id',
            'quantity'           => 'required|numeric|min:0.001',
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
