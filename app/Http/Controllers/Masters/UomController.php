<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Uom;
use App\Services\AuditService;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index(Request $request)
    {
        $query = Uom::with('baseUom');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('uom_name', 'like', "%{$search}%")
                    ->orWhere('uom_code', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $uoms = $query->latest()->paginate($perPage)->withQueryString();
        $baseUoms = Uom::where('status', 'Active')->get();

        return view('masters.uoms.index', compact('uoms', 'baseUoms'));
    }

    public function store(Request $request)
    {
        if ($request->has('name') && !$request->has('uom_name')) {
            $request->merge(['uom_name' => $request->input('name')]);
        }
        if ($request->has('symbol') && !$request->has('uom_code')) {
            $request->merge(['uom_code' => $request->input('symbol')]);
        }
        if ($request->has('code') && !$request->has('uom_code')) {
            $request->merge(['uom_code' => $request->input('code')]);
        }
        if ($request->has('decimal_places') && !$request->has('decimal_precision')) {
            $request->merge(['decimal_precision' => (int) $request->input('decimal_places')]);
        }
        if (!$request->has('category')) {
            $request->merge(['category' => 'Count']);
        }
        if (!$request->has('decimal_precision')) {
            $request->merge(['decimal_precision' => 2]);
        }
        $request->merge(['is_stock_unit' => $request->boolean('is_stock_unit', true)]);

        $validated = $request->validate([
            'uom_code' => 'required|string|max:30|unique:uoms,uom_code',
            'uom_name' => 'required|string|max:100',
            'category' => 'required|in:Weight,Volume,Count,Time,Measurement',
            'decimal_precision' => 'required|integer|min:0|max:4',
            'base_uom_id' => 'nullable|exists:uoms,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'is_stock_unit' => 'required|boolean',
            'status' => 'required|in:Active,Inactive',
        ]);

        $uom = Uom::create($validated);
        AuditService::log('masters', $uom->uom_code, 'create', null, $uom->toArray(), 'Created UOM');

        return redirect()->route('masters.uoms.index')->with('success', "UOM '{$uom->uom_name}' created successfully.");
    }

    public function edit(Uom $uom)
    {
        if (request()->wantsJson()) {
            return response()->json($uom);
        }
        $baseUoms = Uom::where('status', 'Active')->where('id', '!=', $uom->id)->get();
        return view('masters.uoms.edit', compact('uom', 'baseUoms'));
    }

    public function update(Request $request, Uom $uom)
    {
        if ($request->has('name') && !$request->has('uom_name')) {
            $request->merge(['uom_name' => $request->input('name')]);
        }
        if ($request->has('symbol') && !$request->has('uom_code')) {
            $request->merge(['uom_code' => $request->input('symbol')]);
        }
        if ($request->has('code') && !$request->has('uom_code')) {
            $request->merge(['uom_code' => $request->input('code')]);
        }
        if ($request->has('decimal_places') && !$request->has('decimal_precision')) {
            $request->merge(['decimal_precision' => (int) $request->input('decimal_places')]);
        }
        if (!$request->has('category')) {
            $request->merge(['category' => $uom->category ?? 'Count']);
        }
        if (!$request->has('decimal_precision')) {
            $request->merge(['decimal_precision' => $uom->decimal_precision ?? 2]);
        }
        $request->merge(['is_stock_unit' => $request->boolean('is_stock_unit', $uom->is_stock_unit)]);

        $validated = $request->validate([
            'uom_name' => 'required|string|max:100',
            'category' => 'required|in:Weight,Volume,Count,Time,Measurement',
            'decimal_precision' => 'required|integer|min:0|max:4',
            'base_uom_id' => 'nullable|exists:uoms,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'is_stock_unit' => 'required|boolean',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $uom->toArray();
        $uom->update($validated);
        AuditService::log('masters', $uom->uom_code, 'update', $old, $uom->toArray(), 'Updated UOM');

        return redirect()->route('masters.uoms.index')->with('success', "UOM '{$uom->uom_name}' updated successfully.");
    }

    public function destroy(Uom $uom)
    {
        if ($uom->products()->exists() || $uom->materials()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $uom->toArray();
            $uom->delete();
            AuditService::log('masters', $uom->uom_code, 'delete', $old, null, 'Deleted UOM');

            return redirect()->route('masters.uoms.index')->with('success', "UOM deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function toggleStatus(Uom $uom)
    {
        $old = $uom->status;
        $uom->status = ($uom->status === 'Active') ? 'Inactive' : 'Active';
        $uom->save();

        AuditService::log('masters', $uom->uom_code, 'status_change', ['status' => $old], ['status' => $uom->status], 'Toggled UOM status');

        return redirect()->back()->with('success', "UOM '{$uom->uom_name}' status updated to {$uom->status}.");
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
