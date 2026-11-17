<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Role::withCount(['users', 'permissions']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('role_name', 'like', "%{$search}%")
                  ->orWhere('role_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $roles = $query->paginate(15)->withQueryString();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        PermissionService::ensurePermissionsExist();

        $sections = PermissionService::getItemsBySection();
        $actions = PermissionService::getActions();
        $assignedPermissionCodes = [];

        return view('admin.roles.create', compact('sections', 'actions', 'assignedPermissionCodes'));
    }

    public function store(Request $request)
    {
        if ($request->has('name') && !$request->has('role_name')) {
            $request->merge(['role_name' => $request->input('name')]);
        }
        if ($request->has('code') && !$request->has('role_code')) {
            $request->merge(['role_code' => $request->input('code')]);
        }
        if (!$request->has('status')) {
            $request->merge(['status' => 'Active']);
        }

        $validated = $request->validate([
            'role_name'   => 'required|string|max:100|unique:roles,role_name',
            'role_code'   => 'nullable|string|max:50|unique:roles,role_code',
            'description' => 'nullable|string',
            'status'      => 'required|in:Active,Inactive',
            'permissions' => 'nullable|array',
        ]);

        if (empty($validated['role_code'])) {
            $baseCode = Str::snake(strtolower($validated['role_name']));
            $code = $baseCode;
            $counter = 1;
            while (Role::where('role_code', $code)->exists()) {
                $code = $baseCode . '_' . $counter++;
            }
            $validated['role_code'] = $code;
        }

        $roleData = collect($validated)->only(['role_name', 'role_code', 'description', 'status'])->toArray();
        $role = Role::create($roleData);

        $permissionCodes = $request->input('permissions', []);
        PermissionService::syncRolePermissions($role, is_array($permissionCodes) ? $permissionCodes : []);

        AuditService::log('admin', $role->role_code, 'create_role', null, $role->toArray(), 'Created security role with module permissions');

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->role_name}' created successfully.");
    }

    public function edit(Role $role)
    {
        if (request()->wantsJson()) {
            return response()->json($role->load('permissions'));
        }

        PermissionService::ensurePermissionsExist();

        $sections = PermissionService::getItemsBySection();
        $actions = PermissionService::getActions();

        if (in_array(strtolower((string) $role->role_code), ['super_admin', 'super admin'], true)) {
            $assignedPermissionCodes = Permission::pluck('code')->toArray();
        } else {
            $assignedPermissionCodes = $role->permissions()->pluck('code')->toArray();
        }

        return view('admin.roles.edit', compact('role', 'sections', 'actions', 'assignedPermissionCodes'));
    }

    public function update(Request $request, Role $role)
    {
        if ($request->has('name') && !$request->has('role_name')) {
            $request->merge(['role_name' => $request->input('name')]);
        }
        if ($request->has('code') && !$request->has('role_code')) {
            $request->merge(['role_code' => $request->input('code')]);
        }
        if (!$request->has('status')) {
            $request->merge(['status' => $role->status ?? 'Active']);
        }

        $validated = $request->validate([
            'role_name'   => ['required', 'string', 'max:100', Rule::unique('roles', 'role_name')->ignore($role->id)],
            'role_code'   => ['nullable', 'string', 'max:50', Rule::unique('roles', 'role_code')->ignore($role->id)],
            'description' => 'nullable|string',
            'status'      => 'required|in:Active,Inactive',
            'permissions' => 'nullable|array',
        ]);

        if (empty($validated['role_code'])) {
            unset($validated['role_code']);
        }

        $old = $role->toArray();
        $roleData = collect($validated)->only(['role_name', 'role_code', 'description', 'status'])->toArray();
        $role->update($roleData);

        if (($request->has('permissions') || $request->has('permissions_submitted')) &&
            !in_array(strtolower((string) $role->role_code), ['super_admin', 'super admin'], true)) {
            $permissionCodes = $request->input('permissions', []);
            PermissionService::syncRolePermissions($role, is_array($permissionCodes) ? $permissionCodes : []);
        } else {
            Cache::flush();
        }

        AuditService::log('admin', $role->role_code, 'update_role', $old, $role->toArray(), 'Updated role and permissions');

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
            Cache::flush();
            AuditService::log('admin', $code, 'delete_role', $old, null, 'Deleted role');

            return redirect()->route('admin.roles.index')->with('success', "Role deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function show($id = null)
    {
        if ($id) {
            return redirect()->route('admin.roles.edit', $id);
        }
        return redirect()->route('admin.roles.index');
    }
}
