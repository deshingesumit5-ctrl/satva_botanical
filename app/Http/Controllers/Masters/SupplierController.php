<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('gstin', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $suppliers = $query->latest()->paginate($perPage)->withQueryString();

        return view('masters.suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('masters.suppliers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:suppliers,code',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'pan' => 'nullable|string|max:10',
            'payment_terms' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $supplier = Supplier::create($validated);
        AuditService::log('masters', $supplier->code, 'create', null, $supplier->toArray(), 'Created supplier');

        return redirect()->route('masters.suppliers.index')->with('success', "Supplier '{$supplier->name}' created successfully.");
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchaseOrders', 'grns']);
        return view('masters.suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('masters.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'pan' => 'nullable|string|max:10',
            'payment_terms' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $supplier->toArray();
        $supplier->update($validated);
        AuditService::log('masters', $supplier->code, 'update', $old, $supplier->toArray(), 'Updated supplier');

        return redirect()->route('masters.suppliers.index')->with('success', "Supplier '{$supplier->name}' updated successfully.");
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists() || $supplier->grns()->exists()) {
            return back()->with('error', "Cannot delete supplier '{$supplier->name}' because POs or GRNs are linked to this supplier. You can deactivate it instead.");
        }

        $old = $supplier->toArray();
        $supplier->delete();
        AuditService::log('masters', $supplier->code, 'delete', $old, null, 'Deleted supplier');

        return redirect()->route('masters.suppliers.index')->with('success', "Supplier deleted successfully.");
    }

    public function toggleStatus(Supplier $supplier)
    {
        $old = $supplier->status;
        $supplier->status = ($supplier->status === 'Active') ? 'Inactive' : 'Active';
        $supplier->save();

        AuditService::log('masters', $supplier->code, 'status_change', ['status' => $old], ['status' => $supplier->status], 'Toggled supplier status');

        return redirect()->back()->with('success', "Supplier '{$supplier->name}' status updated to {$supplier->status}.");
    }
}
