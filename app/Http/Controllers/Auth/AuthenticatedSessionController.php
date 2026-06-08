<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        /** @var \App\Models\Tenants\User $user */
        $user = $request->user();

        // If the client expects JSON (API clients), return token + data.
        if ($request->wantsJson()) {
            $token = $user->createToken($user->getRememberTokenName());

            $role = $user->roles()->first();
            $permissions = [];
            if ($role) {
                $permissions = $role->permissions()->where('guard_name', 'sanctum')->pluck('name')->toArray();
            }

            return response()->json([
                'success' => true,
                'message' => 'Yay! success to login',
                'token' => $token->plainTextToken,
                'user' => array_merge($user->toArray(), [
                    'permissions' => $permissions,
                    'features' => Feature::all(),
                ]),
            ]);
        }

        // regenerate session for web logins to prevent fixation
        $request->session()->regenerate();

        // For normal browser form submissions, redirect to the Filament panel.
        return redirect()->intended(Filament::getUrl());
    }

    public function destroy(Request $request)
    {
        // Handle API (Sanctum) logout
        if ($request->wantsJson()) {
            // Revoke all tokens for the current user
            $request->user()?->currentAccessToken()?->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
