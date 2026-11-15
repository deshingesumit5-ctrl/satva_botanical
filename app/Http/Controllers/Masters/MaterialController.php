<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Uom;
use App\Services\AuditService;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::with(['baseUom']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('material_code', 'like', "%{$search}%")
                  ->orWhere('grade', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('material_category', $category);
        }

        if ($type = $request->input('type')) {
            $query->where('material_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $materials = $query->latest()->paginate($perPage)->withQueryString();

        return view('masters.materials.index', compact('materials'));
    }

    public function create()
    {
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();
        return view('masters.materials.create', compact('uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'material_code' => 'required|string|max:30|unique:materials,material_code',
            'name' => 'required|string|max:150',
            'material_category' => 'required|in:Raw,Chemical,Solvent,Water,Consumable,Packing',
            'material_type' => 'required|in:Solid,Liquid,Powder,Gas',
            'grade' => 'nullable|string|max:50',
            'hsn_code' => 'nullable|string|max:20',
            'base_uom_id' => 'required|exists:uoms,id',
            'alternate_uom_id' => 'nullable|exists:uoms,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'shelf_life' => 'nullable|integer|min:1',
            'storage_condition' => 'nullable|string',
            'min_stock' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['lot_tracking'] = $request->boolean('lot_tracking', true);
        $validated['qc_required'] = $request->boolean('qc_required', true);

        $material = Material::create($validated);
        AuditService::log('masters', $material->material_code, 'create', null, $material->toArray(), 'Created material');

        return redirect()->route('masters.materials.index')->with('success', "Material '{$material->name}' created successfully.");
    }

    public function show(Material $material)
    {
        $material->load(['baseUom', 'stockLots']);
        return view('masters.materials.show', compact('material'));
    }

    public function edit(Material $material)
    {
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();
        return view('masters.materials.edit', compact('material', 'uoms'));
    }

    public function update(Request $request, Material $material)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'material_category' => 'required|in:Raw,Chemical,Solvent,Water,Consumable,Packing',
            'material_type' => 'required|in:Solid,Liquid,Powder,Gas',
            'grade' => 'nullable|string|max:50',
            'hsn_code' => 'nullable|string|max:20',
            'base_uom_id' => 'required|exists:uoms,id',
            'alternate_uom_id' => 'nullable|exists:uoms,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'shelf_life' => 'nullable|integer|min:1',
            'storage_condition' => 'nullable|string',
            'min_stock' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['lot_tracking'] = $request->boolean('lot_tracking');
        $validated['qc_required'] = $request->boolean('qc_required');

        $old = $material->toArray();
        $material->update($validated);
        AuditService::log('masters', $material->material_code, 'update', $old, $material->toArray(), 'Updated material');

        return redirect()->route('masters.materials.index')->with('success', "Material '{$material->name}' updated successfully.");
    }

    public function destroy(Material $material)
    {
        if ($material->stockLots()->exists() || $material->purchaseOrderItems()->exists()) {
            return back()->with('error', "Cannot delete material '{$material->name}' because it has stock lots or purchase orders linked. You can deactivate it instead.");
        }

        $old = $material->toArray();
        $material->delete();
        AuditService::log('masters', $material->material_code, 'delete', $old, null, 'Deleted material');

        return redirect()->route('masters.materials.index')->with('success', "Material deleted successfully.");
    }

    public function toggleStatus(Material $material)
    {
        $old = $material->status;
        $material->status = ($material->status === 'Active') ? 'Inactive' : 'Active';
        $material->save();

        AuditService::log('masters', $material->material_code, 'status_change', ['status' => $old], ['status' => $material->status], 'Toggled material status');

        return redirect()->back()->with('success', "Material '{$material->name}' status updated to {$material->status}.");
    }
}
