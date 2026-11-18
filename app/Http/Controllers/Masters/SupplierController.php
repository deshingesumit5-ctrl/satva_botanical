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
        if ($request->has('supplier_code') && !$request->has('code')) {
            $request->merge(['code' => $request->input('supplier_code')]);
        }
        if ($request->has('address_line') && !$request->has('address')) {
            $request->merge(['address' => $request->input('address_line')]);
        }
        if ($request->has('payment_terms_days') && !$request->has('payment_terms')) {
            $request->merge(['payment_terms' => $request->input('payment_terms_days')]);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:suppliers,code',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|digits:10',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Za-z0-9]{10}$/'],
            'payment_terms' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (!empty($validated['pan'])) {
            $validated['pan'] = strtoupper($validated['pan']);
        }

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
        if ($request->has('address_line') && !$request->has('address')) {
            $request->merge(['address' => $request->input('address_line')]);
        }
        if ($request->has('payment_terms_days') && !$request->has('payment_terms')) {
            $request->merge(['payment_terms' => $request->input('payment_terms_days')]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|digits:10',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Za-z0-9]{10}$/'],
            'payment_terms' => 'nullable|string|max:100',
            'bank_details' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (!empty($validated['pan'])) {
            $validated['pan'] = strtoupper($validated['pan']);
        }

        $old = $supplier->toArray();
        $supplier->update($validated);
        AuditService::log('masters', $supplier->code, 'update', $old, $supplier->toArray(), 'Updated supplier');

        return redirect()->route('masters.suppliers.index')->with('success', "Supplier '{$supplier->name}' updated successfully.");
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchaseOrders()->exists() || $supplier->grns()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $supplier->toArray();
            $supplier->delete();
            AuditService::log('masters', $supplier->code, 'delete', $old, null, 'Deleted supplier');

            return redirect()->route('masters.suppliers.index')->with('success', "Supplier deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
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
