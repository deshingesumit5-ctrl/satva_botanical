<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Equipment;
use App\Models\Plant;
use App\Models\Uom;
use App\Services\AuditService;
use Illuminate\Http\Request;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::with(['capacityUom', 'plant', 'department']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($equipStatus = $request->input('equipment_status')) {
            $query->where('equipment_status', $equipStatus);
        }

        $perPage = (int) $request->input('per_page', 10);
        $equipments = $query->latest()->paginate($perPage)->withQueryString();
        $uoms = Uom::where('status', 'Active')->get();
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();

        return view('masters.equipments.index', compact('equipments', 'uoms', 'plants', 'departments'));
    }

    public function store(Request $request)
    {
        if ($request->has('equipment_type') && !$request->has('category')) {
            $request->merge(['category' => $request->equipment_type]);
        }
        if (!$request->filled('equipment_status')) {
            if ($request->status === 'Maintenance') {
                $request->merge(['equipment_status' => 'Under Maintenance', 'status' => 'Active']);
            } else {
                $request->merge(['equipment_status' => 'Available']);
            }
        }

        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:equipments,code',
            'name' => 'required|string|max:100',
            'category' => 'nullable|string|max:50',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_uom_id' => 'nullable|exists:uoms,id',
            'plant_id' => 'nullable|exists:plants,id',
            'department_id' => 'nullable|exists:departments,id',
            'location' => 'nullable|string|max:100',
            'equipment_status' => 'required|in:Available,Under Maintenance,Calibrating,In Use',
            'calibration_required' => 'boolean',
            'last_calibration_date' => 'nullable|date',
            'next_calibration_date' => 'nullable|date',
            'maintenance_due_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['calibration_required'] = $request->boolean('calibration_required');

        $equipment = Equipment::create($validated);
        AuditService::log('masters', $equipment->code, 'create', null, $equipment->toArray(), 'Created equipment');

        return redirect()->route('masters.equipments.index')->with('success', "Equipment '{$equipment->name}' created successfully.");
    }

    public function edit(Equipment $equipment)
    {
        if (request()->wantsJson()) {
            return response()->json($equipment);
        }
        $uoms = Uom::where('status', 'Active')->get();
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();
        return view('masters.equipments.edit', compact('equipment', 'uoms', 'plants', 'departments'));
    }

    public function update(Request $request, Equipment $equipment)
    {
        if ($request->has('equipment_type') && !$request->has('category')) {
            $request->merge(['category' => $request->equipment_type]);
        }
        if (!$request->filled('equipment_status')) {
            if ($request->status === 'Maintenance') {
                $request->merge(['equipment_status' => 'Under Maintenance', 'status' => 'Active']);
            } else {
                $request->merge(['equipment_status' => $equipment->equipment_status ?? 'Available']);
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'nullable|string|max:50',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_uom_id' => 'nullable|exists:uoms,id',
            'plant_id' => 'nullable|exists:plants,id',
            'department_id' => 'nullable|exists:departments,id',
            'location' => 'nullable|string|max:100',
            'equipment_status' => 'required|in:Available,Under Maintenance,Calibrating,In Use',
            'calibration_required' => 'boolean',
            'last_calibration_date' => 'nullable|date',
            'next_calibration_date' => 'nullable|date',
            'maintenance_due_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['calibration_required'] = $request->boolean('calibration_required');

        $old = $equipment->toArray();
        $equipment->update($validated);
        AuditService::log('masters', $equipment->code, 'update', $old, $equipment->toArray(), 'Updated equipment');

        return redirect()->route('masters.equipments.index')->with('success', "Equipment '{$equipment->name}' updated successfully.");
    }

    public function destroy(Equipment $equipment)
    {
        if ($equipment->recipeEquipments()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $equipment->toArray();
            $equipment->delete();
            AuditService::log('masters', $equipment->code, 'delete', $old, null, 'Deleted equipment');

            return redirect()->route('masters.equipments.index')->with('success', "Equipment deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function toggleStatus(Equipment $equipment)
    {
        $old = $equipment->status;
        $equipment->status = ($equipment->status === 'Active') ? 'Inactive' : 'Active';
        $equipment->save();

        AuditService::log('masters', $equipment->code, 'status_change', ['status' => $old], ['status' => $equipment->status], 'Toggled equipment status');

        return redirect()->back()->with('success', "Equipment '{$equipment->name}' status updated to {$equipment->status}.");
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
