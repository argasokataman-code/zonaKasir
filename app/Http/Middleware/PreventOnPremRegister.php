<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventOnPremRegister
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('onprem.mode')) {
            return redirect('/member/login');
        }

        return $next($request);
    }
}
