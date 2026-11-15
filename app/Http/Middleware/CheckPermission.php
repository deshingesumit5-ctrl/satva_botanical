<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permissionCode (e.g. "masters.products.view")
     */
    public function handle(Request $request, Closure $next, ?string $permissionCode = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Inactive account check
        if (!$user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => 'Your account is inactive. Please contact your system administrator.']);
        }

        // Super Admin bypasses all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($permissionCode && !$user->hasPermission($permissionCode)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized action. Permission required: ' . $permissionCode], 403);
            }

            return response()->view('errors.403', [
                'permissionCode' => $permissionCode,
                'message' => 'You do not have permission to access this screen or perform this action.'
            ], 403);
        }

        return $next($request);
    }
}
