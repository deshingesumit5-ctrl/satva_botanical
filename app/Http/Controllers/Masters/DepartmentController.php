<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Plant;
use App\Services\AuditService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with('plant');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('department_head', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $departments = $query->latest()->paginate($perPage)->withQueryString();
        $plants = Plant::where('status', 'Active')->get();

        return view('masters.departments.index', compact('departments', 'plants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:departments,code',
            'name' => 'required|string|max:100',
            'plant_id' => 'nullable|exists:plants,id',
            'department_head' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (!empty($validated['plant_id'])) {
            $plant = Plant::find($validated['plant_id']);
            $validated['plant_code'] = $plant ? $plant->code : null;
        }

        $department = Department::create($validated);
        AuditService::log('masters', $department->code, 'create', null, $department->toArray(), 'Created department');

        return redirect()->route('masters.departments.index')->with('success', "Department '{$department->name}' created successfully.");
    }

    public function edit(Department $department)
    {
        if (request()->wantsJson()) {
            return response()->json($department);
        }
        $plants = Plant::where('status', 'Active')->get();
        return view('masters.departments.edit', compact('department', 'plants'));
    }

    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'plant_id' => 'nullable|exists:plants,id',
            'department_head' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (!empty($validated['plant_id'])) {
            $plant = Plant::find($validated['plant_id']);
            $validated['plant_code'] = $plant ? $plant->code : null;
        }

        $old = $department->toArray();
        $department->update($validated);
        AuditService::log('masters', $department->code, 'update', $old, $department->toArray(), 'Updated department');

        return redirect()->route('masters.departments.index')->with('success', "Department '{$department->name}' updated successfully.");
    }

    public function destroy(Department $department)
    {
        if ($department->users()->exists()) {
            return back()->with('error', "Cannot delete department '{$department->name}' as it is assigned to users. You can deactivate it instead.");
        }

        $old = $department->toArray();
        $department->delete();
        AuditService::log('masters', $department->code, 'delete', $old, null, 'Deleted department');

        return redirect()->route('masters.departments.index')->with('success', "Department deleted successfully.");
    }

    public function toggleStatus(Department $department)
    {
        $old = $department->status;
        $department->status = ($department->status === 'Active') ? 'Inactive' : 'Active';
        $department->save();

        AuditService::log('masters', $department->code, 'status_change', ['status' => $old], ['status' => $department->status], 'Toggled department status');

        return redirect()->back()->with('success', "Department '{$department->name}' status updated to {$department->status}.");
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
