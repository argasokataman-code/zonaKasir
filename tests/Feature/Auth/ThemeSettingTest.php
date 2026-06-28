<?php

namespace Tests\Feature\Auth;

use App\Models\Tenants\About;
use App\Models\Tenants\User;
use App\Providers\Filament\TenantPanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

beforeEach(function () {
    $this->user = User::first();
    $this->about = About::first();
});

describe('Theme columns exist', function () {

    it('abouts table has primary_color column', function () {
        $columns = \Schema::getColumnListing('abouts');

        expect($columns)->toContain('primary_color');
    });

    it('abouts table has logo column', function () {
        $columns = \Schema::getColumnListing('abouts');

        expect($columns)->toContain('logo');
    });

    it('abouts table has dark_mode column', function () {
        $columns = \Schema::getColumnListing('abouts');

        expect($columns)->toContain('dark_mode');
    });
});

describe('Theme persistence', function () {

    it('default primary_color is #FF6600', function () {
        expect($this->about->primary_color)->toBe('#FF6600');
    });

    it('can update primary_color', function () {
        $this->about->update(['primary_color' => '#3366FF']);

        $fresh = About::first();
        expect($fresh->primary_color)->toBe('#3366FF');
    });

    it('can update logo path', function () {
        $this->about->update(['logo' => 'stores/my-logo.svg']);

        $fresh = About::first();
        expect($fresh->logo)->toBe('stores/my-logo.svg');
    });

    it('logo defaults to null', function () {
        expect($this->about->logo)->toBeNull();
    });

    it('can update dark_mode', function () {
        $this->about->update(['dark_mode' => true]);

        $fresh = About::first();
        expect($fresh->dark_mode)->toBeTrue();
    });

    it('dark_mode defaults to null', function () {
        expect($this->about->dark_mode)->toBeNull();
    });

    it('theme columns do not affect other existing functionality', function () {
        $originalName = $this->about->shop_name;

        $this->about->update([
            'primary_color' => '#00AA00',
            'logo' => 'new-logo.png',
            'shop_name' => $originalName,
        ]);

        $fresh = About::first();
        expect($fresh->shop_name)->toBe($originalName);
        expect($fresh->primary_color)->toBe('#00AA00');
    });
});

describe('Theme isolation per tenant', function () {

    it('two tenants have independent theme settings', function () {
        $tenantA = About::first();
        $tenantA->update([
            'primary_color' => '#FF0000',
            'logo' => 'tenant-a-logo.svg',
        ]);

        $tenantB = About::create([
            'tenant_id' => 'tenant-b-' . uniqid(),
            'shop_name' => 'Toko B',
            'business_type' => 'retail',
            'shop_location' => 'Lokasi B',
            'primary_color' => '#0000FF',
            'logo' => 'tenant-b-logo.svg',
        ]);

        expect($tenantA->fresh()->primary_color)->toBe('#FF0000');
        expect($tenantB->fresh()->primary_color)->toBe('#0000FF');
        expect($tenantA->fresh()->logo)->toBe('tenant-a-logo.svg');
        expect($tenantB->fresh()->logo)->toBe('tenant-b-logo.svg');
    });
});

describe('Theme via API', function () {

    it('tenant can update theme fields without affecting other data', function () {
        $this->about->update([
            'primary_color' => '#AA00FF',
            'dark_mode' => false,
            'shop_name' => 'Toko Saya',
        ]);

        $fresh = About::first();
        expect($fresh->primary_color)->toBe('#AA00FF');
        expect($fresh->dark_mode)->toBeFalse();
        expect($fresh->shop_name)->toBe('Toko Saya');
    });
});
