<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnpremInstance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnpremHeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->header('X-Onprem-Token') ?? $request->input('token');
        $expected = config('onprem.heartbeat.token');

        if (! $expected || ! hash_equals($expected, (string) $token)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'instance_id' => ['required', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:255'],
            'license_key' => ['nullable', 'string', 'max:255'],
            'app_version' => ['nullable', 'string', 'max:255'],
            'php_version' => ['nullable', 'string', 'max:255'],
        ]);

        OnpremInstance::updateOrCreate(
            ['instance_id' => $validated['instance_id']],
            [
                'domain' => $validated['domain'] ?? null,
                'license_key' => $validated['license_key'] ?? null,
                'app_version' => $validated['app_version'] ?? null,
                'php_version' => $validated['php_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }
}
