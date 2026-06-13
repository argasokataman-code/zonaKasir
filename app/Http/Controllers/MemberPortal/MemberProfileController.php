<?php

namespace App\Http\Controllers\MemberPortal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class MemberProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('member-portal.profile.edit', [
            'member' => $request->user('member'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\Tenants\Member $member */
        $member = $request->user('member');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'current_password' => ['nullable', 'string', 'required_with:password'],
            'password' => ['nullable', 'string', Password::min(6), 'confirmed'],
        ]);

        $member->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? $member->address,
        ]);

        if (! empty($validated['password'])) {
            if (! Hash::check($validated['current_password'], $member->password)) {
                return redirect()->route('member.portal.profile')->withErrors(['current_password' => 'Current password is incorrect']);
            }
            $member->update(['password' => $validated['password']]);
        }

        return redirect()->route('member.portal.profile')->with('status', 'Profile updated successfully');
    }
}
