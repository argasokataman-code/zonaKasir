<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class CheckSanctumTokenExpiry
{
    public function handle(Request $request, Closure $next)
    {
        if ($bearer = $request->bearerToken()) {
            $token = PersonalAccessToken::findToken($bearer);
            if ($token) {
                $expiration = config('sanctum.expiration');
                if ($expiration && $token->created_at->addMinutes($expiration)->isPast()) {
                    $token->delete();
                    return response()->json(['message' => 'Token expired.'], 401);
                }
            }
        }

        return $next($request);
    }
}
