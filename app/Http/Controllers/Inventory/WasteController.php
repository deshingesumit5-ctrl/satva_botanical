<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\WasteRecord;
use App\Services\AuditService;
use Illuminate\Http\Request;

class WasteController extends Controller
{
    public function index(Request $request)
    {
        $query = WasteRecord::with(['batch.product', 'stage', 'warehouse']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('waste_type', 'like', "%{$search}%")
                  ->orWhere('disposal_method', 'like', "%{$search}%")
                  ->orWhereHas('batch', function ($bq) use ($search) {
                      $bq->where('batch_no', 'like', "%{$search}%");
                  });
            });
        }

        if ($type = $request->input('waste_type')) {
            $query->where('waste_type', $type);
        }

        $wasteRecords = $query->latest()->paginate(15)->withQueryString();

        return view('inventory.waste.index', compact('wasteRecords'));
    }

    public function dispose(Request $request, WasteRecord $record)
    {
        $validated = $request->validate([
            'disposal_method' => 'required|string|max:100',
            'disposal_agency' => 'nullable|string|max:100',
            'certificate_number' => 'nullable|string|max:50',
            'disposal_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        $record->update([
            'disposal_method' => $validated['disposal_method'],
            'disposal_agency' => $validated['disposal_agency'] ?? null,
            'certificate_number' => $validated['certificate_number'] ?? null,
            'disposal_date' => $validated['disposal_date'],
            'is_disposed' => true,
            'remarks' => $validated['remarks'] ?? $record->remarks,
        ]);

        AuditService::log('inventory', "WASTE-{$record->id}", 'dispose_waste', null, $record->toArray(), 'Recorded waste/by-product disposal');

        return redirect()->route('inventory.waste.index')->with('success', 'Disposal details recorded successfully.');
    }
}
