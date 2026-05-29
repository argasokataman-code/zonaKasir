<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

/**
 * Standardize JSON Response Format
 * 
 * Ensures all API responses follow a consistent format:
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "message": "...",
 *   "code": 200
 * }
 */
class StandardizeJsonResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Only process JSON responses
        if (! ($response instanceof JsonResponse)) {
            return $response;
        }

        // Get the response data
        $data = $response->getData(true);

        // Already standardized
        if (isset($data['success']) && isset($data['message'])) {
            return $response;
        }

        // Standardize the response
        $standardized = [
            'success' => $response->status() < 400,
            'data' => $data['data'] ?? $data,
            'message' => $data['message'] ?? '',
            'code' => $response->status(),
        ];

        // Remove duplicated 'message' from data if it was there
        if (isset($standardized['data']['message']) && isset($data['message'])) {
            unset($standardized['data']['message']);
        }

        return response()->json($standardized, $response->status());
    }
}
