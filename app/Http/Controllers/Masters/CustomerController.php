<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

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
        $customers = $query->latest()->paginate($perPage)->withQueryString();

        return view('masters.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('masters.customers.create');
    }

    public function store(Request $request)
    {
        if ($request->has('customer_code') && !$request->has('code')) {
            $request->merge(['code' => $request->input('customer_code')]);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:customers,code',
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'pan' => 'nullable|string|max:10',
            'payment_terms' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        $customer = Customer::create($validated);
        AuditService::log('masters', $customer->code, 'create', null, $customer->toArray(), 'Created customer');

        return redirect()->route('masters.customers.index')->with('success', "Customer '{$customer->name}' created successfully.");
    }

    public function show(Customer $customer)
    {
        $customer->load(['salesOrders']);
        return view('masters.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('masters.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'contact_person' => 'nullable|string|max:100',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'billing_address' => 'nullable|string',
            'shipping_address' => 'nullable|string',
            'gstin' => 'nullable|string|max:15',
            'pan' => 'nullable|string|max:10',
            'payment_terms' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $customer->toArray();
        $customer->update($validated);
        AuditService::log('masters', $customer->code, 'update', $old, $customer->toArray(), 'Updated customer');

        return redirect()->route('masters.customers.index')->with('success', "Customer '{$customer->name}' updated successfully.");
    }

    public function destroy(Customer $customer)
    {
        if ($customer->salesOrders()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $customer->toArray();
            $customer->delete();
            AuditService::log('masters', $customer->code, 'delete', $old, null, 'Deleted customer');

            return redirect()->route('masters.customers.index')->with('success', "Customer deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function toggleStatus(Customer $customer)
    {
        $old = $customer->status;
        $customer->status = ($customer->status === 'Active') ? 'Inactive' : 'Active';
        $customer->save();

        AuditService::log('masters', $customer->code, 'status_change', ['status' => $old], ['status' => $customer->status], 'Toggled customer status');

        return redirect()->back()->with('success', "Customer '{$customer->name}' status updated to {$customer->status}.");
    }
}
