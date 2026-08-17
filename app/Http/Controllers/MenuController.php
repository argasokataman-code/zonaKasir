<?php

namespace App\Http\Controllers;

use App\Models\Tenants\About;
use App\Models\Tenants\Table;
use App\Services\TenantContext;
use App\Services\Tenants\MenuDigitalService;

class MenuController extends Controller
{
    public function show(string $token, MenuDigitalService $service)
    {
        $about = About::query()
            ->withoutGlobalScopes()
            ->where('menu_token', $token)
            ->first();

        abort_unless($about, 404, 'Menu tidak ditemukan.');

        TenantContext::set($about->tenant_id);

        $table = request()->integer('table');
        $table = $table > 0
            ? Table::select('number')->where('number', $table)->first()
            : null;

        return view('menu-digital', [
            'about' => $about,
            'items' => $service->items(),
            'table' => $table,
        ]);
    }
}
