<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Plant;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['role', 'department', 'plant']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->input('role_id')) {
            $query->where('role_id', $roleId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $users = $query->latest()->paginate($perPage)->withQueryString();
        $roles = Role::where('status', 'Active')->get();
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();

        return view('admin.users.index', compact('users', 'roles', 'plants', 'departments'));
    }

    public function create()
    {
        $roles = Role::where('status', 'Active')->get();
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();
        return view('admin.users.create', compact('roles', 'plants', 'departments'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'mobile' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'plant_id' => 'nullable|exists:plants,id',
            'status' => 'required|in:Active,Inactive',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // Generate User ID
        $nextNum = User::withTrashed()->count() + 1;
        $validated['user_id'] = 'USR-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        if ($request->hasFile('profile_image')) {
            $validated['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $user = User::create($validated);
        AuditService::log('admin', $user->user_id, 'create_user', null, $user->only(['name', 'email', 'username', 'role_id', 'status']), 'Created system user');

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' created successfully with ID {$user->user_id}.");
    }

    public function edit(User $user)
    {
        if (request()->wantsJson()) {
            return response()->json($user);
        }
        $roles = Role::where('status', 'Active')->get();
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();
        return view('admin.users.edit', compact('user', 'roles', 'plants', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'plant_id' => 'nullable|exists:plants,id',
            'status' => 'required|in:Active,Inactive',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('profile_image')) {
            $validated['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $old = $user->only(['name', 'email', 'mobile', 'role_id', 'department_id', 'plant_id', 'status']);
        $user->update($validated);
        AuditService::log('admin', $user->user_id, 'update_user', $old, $user->only(['name', 'email', 'mobile', 'role_id', 'department_id', 'plant_id', 'status']), 'Updated user details');

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', "You cannot delete your own account.");
        }

        if ($user->isSuperAdmin()) {
            return back()->with('error', "Super Admin account cannot be deleted.");
        }

        $userCode = $user->user_id;
        $user->delete();
        AuditService::log('admin', $userCode, 'delete_user', null, null, 'Deleted user account');

        return redirect()->route('admin.users.index')->with('success', "User account deleted.");
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', "You cannot deactivate your own account.");
        }

        $old = $user->status;
        $user->status = ($user->status === 'Active') ? 'Inactive' : 'Active';
        $user->save();

        AuditService::log('admin', $user->user_id, 'toggle_user_status', ['status' => $old], ['status' => $user->status], 'Toggled user status');

        return redirect()->back()->with('success', "User '{$user->name}' status updated to {$user->status}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $newPassword = Str::random(10) . '@1';
        $user->password = Hash::make($newPassword);
        $user->save();

        AuditService::log('admin', $user->user_id, 'reset_password', null, null, 'Admin reset user password');

        return redirect()->back()->with('success', "Password for {$user->username} has been reset to: {$newPassword} (Please copy this immediately).");
    }

    public function show($id = null)
    {
        return redirect()->back();
    }
}
