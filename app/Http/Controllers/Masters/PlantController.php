<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Services\AuditService;
use Illuminate\Http\Request;

class PlantController extends Controller
{
    public function index(Request $request)
    {
        $query = Plant::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $plants = $query->latest()->paginate($perPage)->withQueryString();

        return view('masters.plants.index', compact('plants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:plants,code',
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'contact' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'gstin' => 'nullable|string|max:15',
            'status' => 'required|in:Active,Inactive',
        ]);

        $plant = Plant::create($validated);
        AuditService::log('masters', $plant->code, 'create', null, $plant->toArray(), 'Created plant');

        return redirect()->route('masters.plants.index')->with('success', "Plant '{$plant->name}' created successfully.");
    }

    public function edit(Plant $plant)
    {
        if (request()->wantsJson()) {
            return response()->json($plant);
        }
        return view('masters.plants.edit', compact('plant'));
    }

    public function update(Request $request, Plant $plant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'nullable|string',
            'contact' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'gstin' => 'nullable|string|max:15',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $plant->toArray();
        $plant->update($validated);
        AuditService::log('masters', $plant->code, 'update', $old, $plant->toArray(), 'Updated plant');

        return redirect()->route('masters.plants.index')->with('success', "Plant '{$plant->name}' updated successfully.");
    }

    public function destroy(Plant $plant)
    {
        // Check FK usage in departments, warehouses, users
        if ($plant->departments()->exists() || $plant->warehouses()->exists() || $plant->users()->exists()) {
            return back()->with('error', "Cannot delete plant '{$plant->name}' as it is in use by departments, warehouses or users. You can deactivate it instead.");
        }

        $old = $plant->toArray();
        $plant->delete();
        AuditService::log('masters', $plant->code, 'delete', $old, null, 'Deleted plant');

        return redirect()->route('masters.plants.index')->with('success', "Plant deleted successfully.");
    }

    public function toggleStatus(Plant $plant)
    {
        $old = $plant->status;
        $plant->status = ($plant->status === 'Active') ? 'Inactive' : 'Active';
        $plant->save();

        AuditService::log('masters', $plant->code, 'status_change', ['status' => $old], ['status' => $plant->status], 'Toggled plant status');

        return redirect()->back()->with('success', "Plant '{$plant->name}' status updated to {$plant->status}.");
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
