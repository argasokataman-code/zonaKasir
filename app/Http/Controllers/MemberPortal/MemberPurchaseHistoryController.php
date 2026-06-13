<?php

namespace App\Http\Controllers\MemberPortal;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberPurchaseHistoryController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\Tenants\Member $member */
        $member = $request->user('member');

        $sellings = $member->sellings()
            ->latest()
            ->paginate(20);

        return view('member-portal.purchases.index', [
            'sellings' => $sellings,
            'currency' => Setting::get('currency', 'IDR'),
        ]);
    }
}
