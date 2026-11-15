<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\StorageLocation;
use App\Models\Warehouse;
use App\Services\AuditService;
use Illuminate\Http\Request;

class StorageLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = StorageLocation::with('warehouse');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location_code', 'like', "%{$search}%")
                  ->orWhere('rack_no', 'like', "%{$search}%")
                  ->orWhere('bin_no', 'like', "%{$search}%");
            });
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $locations = $query->latest()->paginate($perPage)->withQueryString();
        $warehouses = Warehouse::where('status', 'Active')->get();

        return view('masters.locations.index', compact('locations', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_code' => 'required|string|max:30|unique:storage_locations,location_code',
            'name' => 'required|string|max:100',
            'warehouse_id' => 'required|exists:warehouses,id',
            'rack_no' => 'nullable|string|max:30',
            'bin_no' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $location = StorageLocation::create($validated);
        AuditService::log('masters', $location->location_code, 'create', null, $location->toArray(), 'Created storage location');

        return redirect()->route('masters.locations.index')->with('success', "Storage Location '{$location->name}' created successfully.");
    }

    public function edit(StorageLocation $location)
    {
        if (request()->wantsJson()) {
            return response()->json($location);
        }
        $warehouses = Warehouse::where('status', 'Active')->get();
        return view('masters.locations.edit', compact('location', 'warehouses'));
    }

    public function update(Request $request, StorageLocation $location)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'warehouse_id' => 'required|exists:warehouses,id',
            'rack_no' => 'nullable|string|max:30',
            'bin_no' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $location->toArray();
        $location->update($validated);
        AuditService::log('masters', $location->location_code, 'update', $old, $location->toArray(), 'Updated storage location');

        return redirect()->route('masters.locations.index')->with('success', "Storage Location '{$location->name}' updated successfully.");
    }

    public function destroy(StorageLocation $location)
    {
        if ($location->stockLots()->exists()) {
            return back()->with('error', "Cannot delete storage location '{$location->name}' because stock lots are assigned to it. You can deactivate it instead.");
        }

        $old = $location->toArray();
        $location->delete();
        AuditService::log('masters', $location->location_code, 'delete', $old, null, 'Deleted storage location');

        return redirect()->route('masters.locations.index')->with('success', "Storage location deleted successfully.");
    }

    public function toggleStatus(StorageLocation $location)
    {
        $old = $location->status;
        $location->status = ($location->status === 'Active') ? 'Inactive' : 'Active';
        $location->save();

        AuditService::log('masters', $location->location_code, 'status_change', ['status' => $old], ['status' => $location->status], 'Toggled storage location status');

        return redirect()->back()->with('success', "Location '{$location->name}' status updated to {$location->status}.");
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
