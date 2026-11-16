<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::withCount('users');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('role_name', 'like', "%{$search}%")
                  ->orWhere('role_code', 'like', "%{$search}%");
            });
        }

        $roles = $query->paginate(15)->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        if ($request->has('name') && !$request->has('role_name')) {
            $request->merge(['role_name' => $request->input('name')]);
        }
        if ($request->has('code') && !$request->has('role_code')) {
            $request->merge(['role_code' => $request->input('code')]);
        }

        $validated = $request->validate([
            'role_name' => 'required|string|max:100|unique:roles,role_name',
            'role_code' => 'nullable|string|max:50|unique:roles,role_code',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (empty($validated['role_code'])) {
            $validated['role_code'] = Str::snake(strtolower($validated['role_name']));
        }

        $role = Role::create($validated);
        AuditService::log('admin', $role->role_code, 'create_role', null, $role->toArray(), 'Created security role');

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->role_name}' created successfully.");
    }

    public function edit(Role $role)
    {
        if (request()->wantsJson()) {
            return response()->json($role);
        }
        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        if ($request->has('name') && !$request->has('role_name')) {
            $request->merge(['role_name' => $request->input('name')]);
        }
        if ($request->has('code') && !$request->has('role_code')) {
            $request->merge(['role_code' => $request->input('code')]);
        }

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::unique('roles', 'role_name')->ignore($role->id)],
            'role_code' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::unique('roles', 'role_code')->ignore($role->id)],
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $role->toArray();
        $role->update($validated);
        AuditService::log('admin', $role->role_code, 'update_role', $old, $role->toArray(), 'Updated role');

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->role_name}' updated successfully.");
    }

    public function destroy(Role $role)
    {
        if ($role->role_code === 'super_admin' || $role->role_code === 'Super Admin') {
            return back()->with('error', "Super Admin role cannot be deleted.");
        }

        if ($role->users()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $code = $role->role_code;
            $old = $role->toArray();
            $role->permissions()->detach();
            $role->delete();
            AuditService::log('admin', $code, 'delete_role', $old, null, 'Deleted role');

            return redirect()->route('admin.roles.index')->with('success', "Role deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
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
