<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $throttleKey = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        $loginInput = $request->input('login');
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Check if user exists and is active
        $user = User::where($field, $loginInput)->first();
        if ($user && $user->status !== 'Active') {
            RateLimiter::hit($throttleKey, 60);
            return back()->withInput()
                ->with('error', 'Your account is inactive. Please contact your system administrator.')
                ->withErrors(['login' => 'Your account is inactive. Please contact your system administrator.']);
        }

        $credentials = [
            $field => $loginInput,
            'password' => $request->input('password'),
            'status' => 'Active',
        ];

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            AuditService::log('auth', $user->user_id ?? $user->username, 'login', null, ['user_id' => $user->id, 'name' => $user->name], 'User logged in successfully');

            return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withInput()->withErrors([
            'login' => 'Invalid username/email or password.',
        ]);
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditService::log('auth', Auth::user()->user_id, 'logout', ['user_id' => Auth::id()], null, 'User logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been successfully logged out.')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}
