<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;
use Spatie\Activitylog\Facades\LogName;
use Spatie\Activitylog\Facades\Activitylog;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        /** @var \App\Models\Tenants\User $user */
        $user = $request->user();

        // Log successful login
        activity()
            ->performedOn($user)
            ->event('login')
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Login successful');

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
        // Use Filament::getUrl() directly — skip intended() to avoid restoring
        // stale URLs like /member/offline-pos after session expiry.
        return redirect(Filament::getUrl());
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        // Log logout
        if ($user) {
            activity()
                ->performedOn($user)
                ->event('logout')
                ->withProperties([
                    'ip' => $request->ip(),
                ])
                ->log('Logout');
        }

        // Revoke ALL Sanctum tokens (both API and web users)
        $user?->tokens()->delete();

        // Handle API (Sanctum) logout — return JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ]);
        }

        // Web logout — invalidate session, clear cookie, redirect to login
        Auth::guard('web')->logout();

        $session = $request->session();
        $session->invalidate();
        $session->regenerateToken();

        // Force-clear the session cookie from browser
        $sessionName = config('session.cookie', 'laravel_session');
        $response = redirect()->route('filament.tenant.auth.login');
        $response->headers->setCookie(cookie()->forget($sessionName));

        return $response;
    }
}
