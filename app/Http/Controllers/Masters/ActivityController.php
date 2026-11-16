<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('activity_type', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $activities = $query->latest()->paginate($perPage)->withQueryString();

        return view('masters.activities.index', compact('activities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:activities,code',
            'name' => 'required|string|max:100',
            'activity_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'default_duration' => 'nullable|integer|min:1',
            'duration_uom' => 'required|in:Minutes,Hours',
            'instructions' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['start_time_required'] = $request->boolean('start_time_required', true);
        $validated['end_time_required'] = $request->boolean('end_time_required', true);
        $validated['operator_entry_required'] = $request->boolean('operator_entry_required', false);

        $activity = Activity::create($validated);
        AuditService::log('masters', $activity->code, 'create', null, $activity->toArray(), 'Created activity');

        return redirect()->route('masters.activities.index')->with('success', "Activity '{$activity->name}' created successfully.");
    }

    public function edit(Activity $activity)
    {
        if (request()->wantsJson()) {
            return response()->json($activity);
        }
        return view('masters.activities.edit', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        if (!$request->filled('duration_uom')) {
            $request->merge(['duration_uom' => $activity->duration_uom ?? 'Minutes']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'activity_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'default_duration' => 'nullable|integer|min:1',
            'duration_uom' => 'required|in:Minutes,Hours',
            'instructions' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['start_time_required'] = $request->boolean('start_time_required');
        $validated['end_time_required'] = $request->boolean('end_time_required');
        $validated['operator_entry_required'] = $request->boolean('operator_entry_required');

        $old = $activity->toArray();
        $activity->update($validated);
        AuditService::log('masters', $activity->code, 'update', $old, $activity->toArray(), 'Updated activity');

        return redirect()->route('masters.activities.index')->with('success', "Activity '{$activity->name}' updated successfully.");
    }

    public function destroy(Activity $activity)
    {
        if ($activity->recipeStageActivities()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $activity->toArray();
            $activity->delete();
            AuditService::log('masters', $activity->code, 'delete', $old, null, 'Deleted activity');

            return redirect()->route('masters.activities.index')->with('success', "Activity deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function toggleStatus(Activity $activity)
    {
        $old = $activity->status;
        $activity->status = ($activity->status === 'Active') ? 'Inactive' : 'Active';
        $activity->save();

        AuditService::log('masters', $activity->code, 'status_change', ['status' => $old], ['status' => $activity->status], 'Toggled activity status');

        return redirect()->back()->with('success', "Activity '{$activity->name}' status updated to {$activity->status}.");
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
