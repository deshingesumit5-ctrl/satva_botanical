<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\Department;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();
        return view('auth.profile', compact('user', 'plants', 'departments'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'mobile' => 'nullable|digits:10',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $oldData = $user->only(['name', 'email', 'mobile', 'profile_image']);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->mobile = $request->mobile;

        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profiles', 'public');
            $user->profile_image = $path;
        }

        $user->save();

        AuditService::log('auth', $user->user_id, 'profile_update', $oldData, $user->only(['name', 'email', 'mobile', 'profile_image']), 'Updated profile');

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function showPassword()
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $user->password = $request->password;
        $user->save();

        AuditService::log('auth', $user->user_id, 'password_change', null, null, 'User changed password');

        return redirect()->back()->with('success', 'Password changed successfully.');
    }
}
