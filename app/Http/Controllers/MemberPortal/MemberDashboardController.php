<?php

namespace App\Http\Controllers\MemberPortal;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\Tenants\Member $member */
        $member = $request->user('member');

        $recentPurchases = $member->sellings()
            ->latest()
            ->take(5)
            ->get();

        return view('member-portal.dashboard', [
            'member' => $member,
            'recentPurchases' => $recentPurchases,
            'currency' => Setting::get('currency', 'IDR'),
        ]);
    }
}
