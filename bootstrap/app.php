<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            if ($request->hasSession()) {
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => Auth::check()
                        ? 'Your page session refreshed. Please try again.'
                        : 'Your session has expired. Please log in again.',
                    'token' => csrf_token(),
                    'redirect' => Auth::check() ? null : route('login'),
                ], 419);
            }

            if (!Auth::check()) {
                return redirect()->route('login')
                    ->with('error', 'Your session has expired. Please log in again.');
            }

            $previous = url()->previous();
            $target = ($previous && $previous !== $request->fullUrl())
                ? $previous
                : route('dashboard');

            return redirect()->to($target)
                ->with('warning', 'Your page session refreshed. Please try again.');
        });
    })->create();
