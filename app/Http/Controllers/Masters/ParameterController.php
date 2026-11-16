<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Parameter;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function index(Request $request)
    {
        $query = Parameter::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('uom', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('parameter_type')) {
            $query->where('parameter_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $parameters = $query->latest()->paginate($perPage)->withQueryString();

        return view('masters.parameters.index', compact('parameters'));
    }

    public function store(Request $request)
    {
        if ($request->has('default_uom') && !$request->has('uom')) {
            $request->merge(['uom' => $request->default_uom]);
        }
        if ($request->parameter_type === 'Choice') {
            $request->merge(['parameter_type' => 'Dropdown']);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:parameters,code',
            'name' => 'required|string|max:100',
            'parameter_type' => 'required|in:Numeric,Text,Dropdown,Boolean',
            'uom' => 'nullable|string|max:30',
            'decimal_precision' => 'nullable|integer|min:0|max:4',
            'description' => 'nullable|string',
            'options' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (!empty($validated['options']) && is_string($validated['options'])) {
            $validated['options'] = array_map('trim', explode(',', $validated['options']));
        }

        $parameter = Parameter::create($validated);
        AuditService::log('masters', $parameter->code, 'create', null, $parameter->toArray(), 'Created parameter');

        return redirect()->route('masters.parameters.index')->with('success', "Parameter '{$parameter->name}' created successfully.");
    }

    public function edit(Parameter $parameter)
    {
        if (request()->wantsJson()) {
            return response()->json($parameter);
        }
        return view('masters.parameters.edit', compact('parameter'));
    }

    public function update(Request $request, Parameter $parameter)
    {
        if ($request->has('default_uom') && !$request->has('uom')) {
            $request->merge(['uom' => $request->default_uom]);
        }
        if ($request->parameter_type === 'Choice') {
            $request->merge(['parameter_type' => 'Dropdown']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'parameter_type' => 'required|in:Numeric,Text,Dropdown,Boolean',
            'uom' => 'nullable|string|max:30',
            'decimal_precision' => 'nullable|integer|min:0|max:4',
            'description' => 'nullable|string',
            'options' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (!empty($validated['options']) && is_string($validated['options'])) {
            $validated['options'] = array_map('trim', explode(',', $validated['options']));
        }

        $old = $parameter->toArray();
        $parameter->update($validated);
        AuditService::log('masters', $parameter->code, 'update', $old, $parameter->toArray(), 'Updated parameter');

        return redirect()->route('masters.parameters.index')->with('success', "Parameter '{$parameter->name}' updated successfully.");
    }

    public function destroy(Parameter $parameter)
    {
        if ($parameter->recipeParameters()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $parameter->toArray();
            $parameter->delete();
            AuditService::log('masters', $parameter->code, 'delete', $old, null, 'Deleted parameter');

            return redirect()->route('masters.parameters.index')->with('success', "Parameter deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function toggleStatus(Parameter $parameter)
    {
        $old = $parameter->status;
        $parameter->status = ($parameter->status === 'Active') ? 'Inactive' : 'Active';
        $parameter->save();

        AuditService::log('masters', $parameter->code, 'status_change', ['status' => $old], ['status' => $parameter->status], 'Toggled parameter status');

        return redirect()->back()->with('success', "Parameter '{$parameter->name}' status updated to {$parameter->status}.");
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
