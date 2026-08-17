<?php

namespace App\Http\Controllers;

use App\Models\Tenants\About;
use App\Models\Tenants\Selling;
use App\Services\TenantContext;

class StrukShareController extends Controller
{
    public function show(string $token)
    {
        $selling = Selling::query()
            ->withoutGlobalScopes()
            ->with([
                'sellingDetails.product',
                'table:id,number',
                'member:id,name',
                'paymentMethod:id,name',
            ])
            ->where('share_token', $token)
            ->where('status', 'paid')
            ->first();

        abort_unless($selling, 404, 'Struk tidak ditemukan.');

        TenantContext::set($selling->tenant_id);

        $about = About::select('id', 'shop_name', 'shop_location', 'business_type', 'logo', 'primary_color')->first();

        return view('struk-digital', [
            'selling' => $selling,
            'about' => $about,
        ]);
    }
}
