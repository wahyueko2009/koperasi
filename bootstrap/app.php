<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'login.context' => \App\Http\Middleware\EnsureLoginContext::class,
            'administrator.only' => \App\Http\Middleware\EnsureAdministrator::class,
            'unit.usaha.only' => \App\Http\Middleware\EnsureUnitUsaha::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            if ($request->isMethod('post') && $request->routeIs('logout')) {
                return redirect()
                    ->route('login')
                    ->with('success', 'Sesi Anda sudah berakhir. Anda sudah keluar dan silakan login lagi.');
            }

            return back()
                ->withErrors([
                    'session' => 'Halaman ini sudah kedaluwarsa. Silakan muat ulang halaman lalu coba lagi.',
                ]);
        });
    })->create();
