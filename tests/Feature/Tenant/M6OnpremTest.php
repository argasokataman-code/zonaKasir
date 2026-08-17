<?php

use App\Console\Commands\BackupDatabase;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenants\User;
use App\Services\PlanAccessService;
use Illuminate\Support\Facades\File;
use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

describe('M6 — Onprem Kafe Komersial', function () {
    beforeEach(function () {
        $this->backupPath = storage_path('app/backups/db');
        File::ensureDirectoryExists($this->backupPath);
        foreach (File::files($this->backupPath) as $f) {
            File::delete($f->getRealPath());
        }
        $this->tenantId = 'toko_testing';
    });

    afterEach(function () {
        foreach (File::files($this->backupPath) as $f) {
            File::delete($f->getRealPath());
        }
    });

    // ── FR-9.5: Backup Database ──

    test('BACKUP-1: backup:database creates gzipped SQL file', function () {
        if (! exec('which pg_dump')) {
            $this->markTestSkipped('pg_dump not available');
        }

        $exitCode = Artisan::call(BackupDatabase::class);
        $output = Artisan::output();

        expect($exitCode)->toBe(0);
        expect($output)->toContain('✅ Backup saved');

        $files = File::files($this->backupPath);
        expect($files)->not->toBeEmpty();

        $file = $files[0];
        expect($file->getFilename())->toMatch('/^zonaKasir_\d{4}-\d{2}-\d{2}_\d{6}\.sql\.gz$/');
        expect($file->getSize())->toBeGreaterThan(0);
    });

    // ── FR-9.6: PlanAccessService Cafe Features ──

    test('PLAN-1: tenant tanpa plan bisa akses lite cafe features', function () {
        $service = app(PlanAccessService::class);

        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_open_bill'))->toBeTrue();
        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_table'))->toBeTrue();
        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_struk'))->toBeTrue();
    });

    test('PLAN-2: tenant tanpa plan TIDAK bisa akses pro cafe features', function () {
        $service = app(PlanAccessService::class);

        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_split_bill'))->toBeFalse();
        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_kds'))->toBeFalse();
        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_shift_xz'))->toBeFalse();
    });

    test('PLAN-3: tenant dengan Pro plan bisa akses semua cafe features', function () {
        $service = app(PlanAccessService::class);

        $plan = Plan::create([
            'name' => 'Kafe Pro',
            'slug' => 'kafe-pro',
            'features' => ['cafe_open_bill', 'cafe_table', 'cafe_struk', 'cafe_split_bill', 'cafe_kds', 'cafe_shift_xz'],
        ]);

        // Update existing subscription from trait instead of creating new one
        Subscription::where('tenant_id', $this->tenantId)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
        ]);

        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_split_bill'))->toBeTrue();
        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_kds'))->toBeTrue();
        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_shift_xz'))->toBeTrue();
    });

    test('PLAN-4: tenant dengan Lite plan TIDAK bisa akses pro features', function () {
        $service = app(PlanAccessService::class);

        $plan = Plan::create([
            'name' => 'Kafe Lite',
            'slug' => 'kafe-lite',
            'features' => ['cafe_open_bill', 'cafe_table', 'cafe_struk'],
        ]);

        Subscription::where('tenant_id', $this->tenantId)->update([
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'starts_at' => now(),
        ]);

        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_split_bill'))->toBeFalse();
        expect($service->canAccessCafeFeature($this->tenantId, 'cafe_kds'))->toBeFalse();
    });

    // ── FR-9.2: Internet Badge ──

    test('INTERNET-1: marketing content page shows internet notice', function () {
        $user = User::first();

        $response = $this->actingAs($user)->get('/member/marketing-content');

        $response->assertStatus(200);
        $response->assertSee(__('Fitur ini memerlukan koneksi internet'));
    });
});
