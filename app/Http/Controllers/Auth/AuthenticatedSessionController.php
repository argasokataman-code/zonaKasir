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

        // regenerate session for web logins to prevent fixation
        $request->session()->regenerate();

        /** @var \App\Models\Tenants\User $user */
        $user = $request->user();

        // If the client expects JSON (API clients), return token + data.
        if ($request->wantsJson()) {
            $token = $user->createToken($user->getRememberTokenName());

            return response()->json([
                'success' => true,
                'message' => 'Yay! success to login',
                'data' => array_merge($user->toArray(), [
                    'token' => $token->plainTextToken,
                    'permissions' => $user->roles()->first()->permissions()->where('guard_name', 'sanctum')->pluck('name')->toArray(),
                    'features' => Feature::all(),
                ]),
            ]);
        }

        // For normal browser form submissions, redirect to the Filament panel.
        return redirect()->intended(Filament::getUrl());
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
