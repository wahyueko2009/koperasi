<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUnitUsaha
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user()?->canAccessUnitUsaha(),
            403,
            'Menu ini hanya dapat diakses oleh petugas unit usaha atau administrator.'
        );

        return $next($request);
    }
}
