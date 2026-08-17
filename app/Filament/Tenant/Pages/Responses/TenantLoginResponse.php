<?php

namespace App\Filament\Tenant\Pages\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class TenantLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        // Cache business type in session for navigation building
        try {
            $user = $request->user();
            if ($user && $user->tenant_id) {
                $about = \App\Models\Tenants\About::select('id', 'business_type')->first();
                // Always refresh on login (not just if missing)
                session(['tenant_business_type' => $about?->business_type]);
            }
        } catch (\Throwable) {
            // ignore
        }

        return redirect()->intended(route('filament.tenant.pages.dashboard'));
    }
}