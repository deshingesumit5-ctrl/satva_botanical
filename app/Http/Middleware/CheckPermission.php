<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
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
     * @param  string|null  $permissionCode (e.g. "masters.products.view")
     */
    public function handle(Request $request, Closure $next, ?string $permissionCode = null): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('role');

        // Inactive account or inactive role check
        if (!$user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'login'    => 'Your account or assigned role is inactive. Please contact your system administrator.',
                'username' => 'Your account or assigned role is inactive. Please contact your system administrator.',
            ]);
        }

        // Super Admin bypasses all checks
        if (!$user->isSuperAdmin()) {
            $required = $permissionCode ?: PermissionService::resolveRoutePermission(
                $request->route()?->getName(),
                $request->method(),
                $request->path()
            );

            if ($required) {
                $codes = is_array($required) ? $required : [$required];
                if (!$user->hasAnyPermission($codes)) {
                    $displayCode = implode(' or ', $codes);

                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Unauthorized action. Permission required: ' . $displayCode,
                        ], 403);
                    }

                    return response()->view('errors.403', [
                        'permissionCode' => $displayCode,
                        'message'        => 'You do not have permission to access this screen or perform this action.',
                    ], 403);
                }
            }
        }

        $response = $next($request);

        // When a form is submitted via AJAX expecting JSON and the controller returns a redirect,
        // return structured JSON so modal forms stay open on error or close with toast + refresh on success.
        if ($request->expectsJson() && $response instanceof \Illuminate\Http\RedirectResponse) {
            $session = $request->hasSession() ? $request->session() : null;

            if ($session && $session->has('errors')) {
                $errorBag = $session->get('errors')->getBag('default')->toArray();
                $session->forget('errors');
                $firstErr = collect($errorBag)->flatten()->first() ?? 'Please check the highlighted fields.';
                return response()->json([
                    'success' => false,
                    'message' => $firstErr,
                    'errors'  => $errorBag,
                ], 422);
            }

            if ($session && $session->has('error')) {
                $errMsg = $session->pull('error');
                return response()->json([
                    'success' => false,
                    'message' => $errMsg,
                    'errors'  => ['_general' => [$errMsg]],
                ], 422);
            }

            $successMsg = $session
                ? ($session->pull('success') ?? $session->pull('warning') ?? 'Saved successfully.')
                : 'Saved successfully.';

            return response()->json([
                'success'  => true,
                'message'  => $successMsg,
                'redirect' => $response->getTargetUrl(),
            ], 200);
        }

        return $response;
    }
}
