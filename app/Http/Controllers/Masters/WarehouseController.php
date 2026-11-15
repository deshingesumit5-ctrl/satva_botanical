<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\Warehouse;
use App\Services\AuditService;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $query = Warehouse::with(['plant', 'storageLocations']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $perPage = (int) $request->input('per_page', 10);
        $warehouses = $query->latest()->paginate($perPage)->withQueryString();
        $plants = Plant::where('status', 'Active')->get();

        return view('masters.warehouses.index', compact('warehouses', 'plants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:warehouses,code',
            'name' => 'required|string|max:100',
            'type' => 'required|in:Raw Material,Chemical,WIP,Finished Goods,Recovery,Waste,Rejected',
            'plant_id' => 'nullable|exists:plants,id',
            'address' => 'nullable|string',
            'storage_condition' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
        ]);

        $warehouse = Warehouse::create($validated);
        AuditService::log('masters', $warehouse->code, 'create', null, $warehouse->toArray(), 'Created warehouse');

        return redirect()->route('masters.warehouses.index')->with('success', "Warehouse '{$warehouse->name}' created successfully.");
    }

    public function edit(Warehouse $warehouse)
    {
        if (request()->wantsJson()) {
            return response()->json($warehouse);
        }
        $plants = Plant::where('status', 'Active')->get();
        return view('masters.warehouses.edit', compact('warehouse', 'plants'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:Raw Material,Chemical,WIP,Finished Goods,Recovery,Waste,Rejected',
            'plant_id' => 'nullable|exists:plants,id',
            'address' => 'nullable|string',
            'storage_condition' => 'nullable|string|max:255',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $warehouse->toArray();
        $warehouse->update($validated);
        AuditService::log('masters', $warehouse->code, 'update', $old, $warehouse->toArray(), 'Updated warehouse');

        return redirect()->route('masters.warehouses.index')->with('success', "Warehouse '{$warehouse->name}' updated successfully.");
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->storageLocations()->exists() || $warehouse->stockLots()->exists()) {
            return back()->with('error', "Cannot delete warehouse '{$warehouse->name}' because it contains storage locations or stock lots. You can deactivate it instead.");
        }

        $old = $warehouse->toArray();
        $warehouse->delete();
        AuditService::log('masters', $warehouse->code, 'delete', $old, null, 'Deleted warehouse');

        return redirect()->route('masters.warehouses.index')->with('success', "Warehouse deleted successfully.");
    }

    public function toggleStatus(Warehouse $warehouse)
    {
        $old = $warehouse->status;
        $warehouse->status = ($warehouse->status === 'Active') ? 'Inactive' : 'Active';
        $warehouse->save();

        AuditService::log('masters', $warehouse->code, 'status_change', ['status' => $old], ['status' => $warehouse->status], 'Toggled warehouse status');

        return redirect()->back()->with('success', "Warehouse '{$warehouse->name}' status updated to {$warehouse->status}.");
    }

    public function create()
    {
        return redirect()->back();
    }

    public function show($id = null)
    {
        return redirect()->back();
    }
}
