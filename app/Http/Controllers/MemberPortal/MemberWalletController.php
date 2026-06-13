<?php

namespace App\Http\Controllers\MemberPortal;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberWalletController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\Tenants\Member $member */
        $member = $request->user('member');

        $transactions = $member->walletTransactions()
            ->latest()
            ->paginate(20);

        return view('member-portal.wallet.index', [
            'member' => $member,
            'transactions' => $transactions,
            'currency' => Setting::get('currency', 'IDR'),
        ]);
    }
}
