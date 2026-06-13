<?php

namespace App\Http\Controllers\MemberPortal;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Voucher;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberVoucherController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\Tenants\Member $member */
        $member = $request->user('member');

        $vouchers = Voucher::where(function ($q) use ($member) {
            $q->whereNull('member_id')->orWhere('member_id', $member->id);
        })
            ->where('expired', '>=', now())
            ->where('kuota', '>', 0)
            ->latest()
            ->paginate(20);

        return view('member-portal.vouchers.index', [
            'vouchers' => $vouchers,
        ]);
    }
}
