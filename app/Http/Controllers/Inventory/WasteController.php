<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Stage;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Models\WasteRecord;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WasteController extends Controller
{
    public function index(Request $request)
    {
        $query = WasteRecord::with(['batch.product', 'stage', 'warehouse']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('waste_type', 'like', "%{$search}%")
                  ->orWhere('item_name', 'like', "%{$search}%")
                  ->orWhereHas('batch', function ($bq) use ($search) {
                      $bq->where('batch_number', 'like', "%{$search}%");
                  });
            });
        }

        if ($type = $request->input('waste_type')) {
            $query->where('waste_type', $type);
        }

        $wasteRecords = $query->latest()->paginate(15)->withQueryString();
        $batches = Batch::latest()->get();
        $stages = Stage::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->get();

        return view('inventory.waste.index', compact('wasteRecords', 'batches', 'stages', 'uoms', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'batch_id'      => 'required|exists:batches,id',
            'waste_type'    => 'required|string|max:100',
            'item_name'     => 'required|string|max:150',
            'quantity'      => 'required|numeric|min:0.001',
            'uom_id'        => 'required|exists:uoms,id',
            'warehouse_id'  => 'nullable|exists:warehouses,id',
            'disposal_date' => 'nullable|date',
            'remarks'       => 'nullable|string',
        ]);

        $record = WasteRecord::create([
            'batch_id'      => $validated['batch_id'],
            'stage_id'      => $request->input('stage_id'),
            'waste_type'    => $validated['waste_type'],
            'item_name'     => $validated['item_name'],
            'quantity'      => $validated['quantity'],
            'uom_id'        => $validated['uom_id'],
            'warehouse_id'  => $validated['warehouse_id'] ?? null,
            'disposal_date' => $validated['disposal_date'] ?? now()->toDateString(),
            'remarks'       => $validated['remarks'] ?? null,
            'created_by'    => Auth::id() ?? 1,
        ]);

        AuditService::log('inventory', "WASTE-{$record->id}", 'create_waste_record', null, $record->toArray(), 'Recorded waste/spent residue');

        return redirect()->route('inventory.waste.index')->with('success', 'Waste / By-Product record saved successfully.');
    }

    public function dispose(Request $request, WasteRecord $record)
    {
        $validated = $request->validate([
            'disposal_method'    => 'required|string|max:100',
            'disposal_agency'    => 'nullable|string|max:100',
            'certificate_number' => 'nullable|string|max:50',
            'disposal_date'      => 'required|date',
            'remarks'            => 'nullable|string',
        ]);

        $record->update([
            'disposal_date' => $validated['disposal_date'],
            'remarks'       => trim(($validated['disposal_method'] ?? '') . ' - ' . ($validated['remarks'] ?? $record->remarks)),
        ]);

        AuditService::log('inventory', "WASTE-{$record->id}", 'dispose_waste', null, $record->toArray(), 'Recorded waste/by-product disposal');

        return redirect()->route('inventory.waste.index')->with('success', 'Disposal details recorded successfully.');
    }
}
