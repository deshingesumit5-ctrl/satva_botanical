<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionMatrixController extends Controller
{
    public function index(Request $request)
    {
        PermissionService::ensurePermissionsExist();

        $roles = Role::orderBy('id')->get();
        $defaultRole = $roles->firstWhere('role_code', '!=', 'super_admin') ?? $roles->first();
        $selectedRoleId = $request->input('role_id', $defaultRole?->id);
        $selectedRole = $selectedRoleId ? Role::with('permissions')->find($selectedRoleId) : null;

        $sections = PermissionService::getItemsBySection();
        $actions = PermissionService::getActions();

        if ($selectedRole && in_array(strtolower((string) $selectedRole->role_code), ['super_admin', 'super admin'], true)) {
            $assignedPermissionCodes = Permission::pluck('code')->toArray();
        } else {
            $assignedPermissionCodes = $selectedRole ? $selectedRole->permissions->pluck('code')->toArray() : [];
        }

        return view('admin.permissions.index', compact(
            'roles',
            'selectedRole',
            'sections',
            'actions',
            'assignedPermissionCodes'
        ));
    }

    public function save(Request $request)
    {
        $request->validate([
            'role_id'     => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::findOrFail($request->role_id);

        if (in_array(strtolower((string) $role->role_code), ['super_admin', 'super admin'], true)) {
            return back()->with('warning', 'Super Admin inherently possesses all system permissions.');
        }

        $permissionCodes = $request->input('permissions', []);
        PermissionService::syncRolePermissions($role, is_array($permissionCodes) ? $permissionCodes : []);

        AuditService::log(
            'admin',
            $role->role_code,
            'update_permissions',
            null,
            ['count' => $role->permissions()->count()],
            "Updated permissions for role {$role->role_name}"
        );

        return redirect()->route('admin.permissions.index', ['role_id' => $role->id])
            ->with('success', "Permissions updated successfully for {$role->role_name}.");
    }
}
