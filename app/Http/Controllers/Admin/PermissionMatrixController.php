<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PermissionMatrixController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::where('status', 'Active')->get();
        $selectedRoleId = $request->input('role_id', $roles->firstWhere('role_code', '!=', 'super_admin')->id ?? $roles->first()->id);
        $selectedRole = Role::with('permissions')->find($selectedRoleId);

        $matrix = PermissionService::getModuleMatrix();
        $allPermissions = Permission::all()->keyBy('code');

        $assignedPermissionCodes = $selectedRole ? $selectedRole->permissions->pluck('code')->toArray() : [];

        return view('admin.permissions.index', compact('roles', 'selectedRole', 'matrix', 'allPermissions', 'assignedPermissionCodes'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::findOrFail($request->role_id);

        if ($role->role_code === 'super_admin') {
            return back()->with('warning', 'Super Admin inherently possesses all system permissions.');
        }

        $permissionCodes = $request->input('permissions', []);
        $permissionIds = Permission::whereIn('code', $permissionCodes)->pluck('id')->toArray();

        $role->permissions()->sync($permissionIds);

        // Clear cached permissions
        Cache::flush();

        AuditService::log('admin', $role->role_code, 'update_permissions', null, ['count' => count($permissionIds)], "Updated permissions for role {$role->role_name}");

        return redirect()->route('admin.permissions.index', ['role_id' => $role->id])->with('success', "Permissions updated successfully for {$role->role_name}.");
    }
}
