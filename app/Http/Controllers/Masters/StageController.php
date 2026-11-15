<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Stage;
use App\Services\AuditService;
use Illuminate\Http\Request;

class StageController extends Controller
{
    public function index(Request $request)
    {
        $query = Stage::with('defaultEquipment');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('stage_name', 'like', "%{$search}%")
                  ->orWhere('stage_code', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $stages = $query->latest()->paginate($perPage)->withQueryString();
        $equipments = Equipment::where('status', 'Active')->get();

        return view('masters.stages.index', compact('stages', 'equipments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stage_code' => 'required|string|max:30|unique:stages,stage_code',
            'stage_name' => 'required|string|max:100',
            'category' => 'required|in:Processing,Treatment,Separation,Drying',
            'default_equipment_id' => 'nullable|exists:equipments,id',
            'default_duration' => 'nullable|integer|min:1',
            'duration_uom' => 'required|in:Minutes,Hours',
            'instructions' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['activity_required'] = $request->boolean('activity_required', true);
        $validated['material_input_required'] = $request->boolean('material_input_required', true);
        $validated['material_output_required'] = $request->boolean('material_output_required', true);
        $validated['qc_available'] = $request->boolean('qc_available', true);

        $stage = Stage::create($validated);
        AuditService::log('masters', $stage->stage_code, 'create', null, $stage->toArray(), 'Created stage');

        return redirect()->route('masters.stages.index')->with('success', "Stage '{$stage->stage_name}' created successfully.");
    }

    public function edit(Stage $stage)
    {
        if (request()->wantsJson()) {
            return response()->json($stage);
        }
        $equipments = Equipment::where('status', 'Active')->get();
        return view('masters.stages.edit', compact('stage', 'equipments'));
    }

    public function update(Request $request, Stage $stage)
    {
        $validated = $request->validate([
            'stage_name' => 'required|string|max:100',
            'category' => 'required|in:Processing,Treatment,Separation,Drying',
            'default_equipment_id' => 'nullable|exists:equipments,id',
            'default_duration' => 'nullable|integer|min:1',
            'duration_uom' => 'required|in:Minutes,Hours',
            'instructions' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['activity_required'] = $request->boolean('activity_required');
        $validated['material_input_required'] = $request->boolean('material_input_required');
        $validated['material_output_required'] = $request->boolean('material_output_required');
        $validated['qc_available'] = $request->boolean('qc_available');

        $old = $stage->toArray();
        $stage->update($validated);
        AuditService::log('masters', $stage->stage_code, 'update', $old, $stage->toArray(), 'Updated stage');

        return redirect()->route('masters.stages.index')->with('success', "Stage '{$stage->stage_name}' updated successfully.");
    }

    public function destroy(Stage $stage)
    {
        if ($stage->recipeStages()->exists() || $stage->batchStageExecutions()->exists()) {
            return back()->with('error', "Cannot delete stage '{$stage->stage_name}' as it is mapped in recipes or batches. You can deactivate it instead.");
        }

        $old = $stage->toArray();
        $stage->delete();
        AuditService::log('masters', $stage->stage_code, 'delete', $old, null, 'Deleted stage');

        return redirect()->route('masters.stages.index')->with('success', "Stage deleted successfully.");
    }

    public function toggleStatus(Stage $stage)
    {
        $old = $stage->status;
        $stage->status = ($stage->status === 'Active') ? 'Inactive' : 'Active';
        $stage->save();

        AuditService::log('masters', $stage->stage_code, 'status_change', ['status' => $old], ['status' => $stage->status], 'Toggled stage status');

        return redirect()->back()->with('success', "Stage '{$stage->stage_name}' status updated to {$stage->status}.");
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
