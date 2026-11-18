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
            'name'          => 'required|string|max:100',
            'employee_code' => 'nullable|string|max:30|unique:users,user_id',
            'email'         => 'required|email|max:255|unique:users,email',
            'username'      => 'required|string|max:50|unique:users,username',
            'password'      => 'required|string|min:8|confirmed',
            'mobile'        => 'nullable|digits:10',
            'role_id'       => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'plant_id'      => 'nullable|exists:plants,id',
            'status'        => 'required|in:Active,Inactive',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        // Note: User model has 'password' => 'hashed' cast, so password is hashed once automatically by Eloquent.

        // Generate unique User ID
        if (!empty($validated['employee_code'])) {
            $validated['user_id'] = $validated['employee_code'];
        } else {
            $nextNum = User::withTrashed()->count() + 1;
            $userId = 'USR-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            while (User::withTrashed()->where('user_id', $userId)->exists()) {
                $nextNum++;
                $userId = 'USR-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }
            $validated['user_id'] = $userId;
        }
        unset($validated['employee_code']);

        if ($request->hasFile('profile_image')) {
            $validated['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        $user = User::create($validated);
        AuditService::log('admin', $user->user_id, 'create_user', null, $user->only(['name', 'email', 'username', 'role_id', 'status']), 'Created system user');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "User '{$user->name}' created successfully with ID {$user->user_id}.",
                'data'    => $user,
            ]);
        }

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
        if (trim((string) $request->input('password', '')) === '') {
            $request->merge(['password' => null, 'password_confirmation' => null]);
        }

        $passwordRule = $request->has('password_confirmation')
            ? 'nullable|string|min:8|confirmed'
            : 'nullable|string|min:8';

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'employee_code' => ['nullable', 'string', 'max:30', Rule::unique('users', 'user_id')->ignore($user->id)],
            'email'         => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile'        => 'nullable|digits:10',
            'password'      => $passwordRule,
            'role_id'       => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'plant_id'      => 'nullable|exists:plants,id',
            'status'        => 'required|in:Active,Inactive',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        if (!empty($validated['employee_code'])) {
            $validated['user_id'] = $validated['employee_code'];
        }
        unset($validated['employee_code']);

        if ($request->hasFile('profile_image')) {
            $validated['profile_image'] = $request->file('profile_image')->store('profiles', 'public');
        }

        if ($request->filled('password')) {
            // User model has 'password' => 'hashed' cast, so Eloquent hashes once automatically.
            $validated['password'] = $request->input('password');
        } else {
            unset($validated['password']);
        }

        // Never allow username or empty password mass-assignment on update
        unset($validated['username']);

        $old = $user->only(['name', 'email', 'mobile', 'role_id', 'department_id', 'plant_id', 'status']);
        $user->update($validated);
        AuditService::log('admin', $user->user_id, 'update_user', $old, $user->only(['name', 'email', 'mobile', 'role_id', 'department_id', 'plant_id', 'status']), 'Updated user details');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "User '{$user->name}' updated successfully.",
                'data'    => $user->fresh(['role', 'plant', 'department']),
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', "You cannot delete your own account.");
        }

        if ($user->user_id === 'USR-0001' || $user->isSuperAdmin()) {
            return back()->with('error', "Super Admin account cannot be deleted.");
        }

        try {
            $userCode = $user->user_id;
            $user->delete();
            AuditService::log('admin', $userCode, 'delete_user', null, null, 'Deleted user account');

            return redirect()->route('admin.users.index')->with('success', "User account deleted.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
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
        $user->password = $newPassword;
        $user->save();

        AuditService::log('admin', $user->user_id, 'reset_password', null, null, 'Admin reset user password');

        return redirect()->back()->with('success', "Password for {$user->username} has been reset to: {$newPassword} (Please copy this immediately).");
    }

    public function show($id = null)
    {
        return redirect()->back();
    }
}
