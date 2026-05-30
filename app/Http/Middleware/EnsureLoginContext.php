<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLoginContext
{
    public function handle(Request $request, Closure $next, string $context): Response
    {
        $currentContext = (string) $request->session()->get('login_context');

        if ($currentContext !== $context) {
            $targetRoute = $currentContext === 'anggota' ? 'member-portal' : 'dashboard';

            return redirect()
                ->route($targetRoute)
                ->with('success', 'Akses halaman disesuaikan dengan konteks login yang aktif.');
        }

        return $next($request);
    }
}
