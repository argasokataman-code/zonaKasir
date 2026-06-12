<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class WithdrawalRateLimit
{
    public function handle(Request $request, Closure $next): mixed
    {
        $key = 'withdrawal:' . auth()->id();
        
        // 5 requests per minute for withdrawal endpoints
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'message' => 'Too many withdrawal requests. Try again in ' . $seconds . ' seconds.',
            ], 429);
        }

        RateLimiter::hit($key, 60); // 1 minute decay

        return $next($request);
    }
}
