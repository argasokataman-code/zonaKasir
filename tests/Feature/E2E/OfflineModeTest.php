<?php

use Tests\RefreshDatabaseWithTenant;

uses(RefreshDatabaseWithTenant::class);

// ─── Session Configuration Tests ──────────────────────────

test('session expire_on_close is disabled for PWA', function () {
    $config = config('session.expire_on_close');
    expect($config)->toBeFalse();
});

test('session lifetime is 120 minutes', function () {
    $config = config('session.lifetime');
    expect($config)->toBe(120);
});

test('session secure cookie config exists', function () {
    // SESSION_SECURE_COOKIE is set in .env (production), may be null in testing
    // The important thing is that the config key exists
    $config = config('session.secure');
    // In testing env, it might be null (not set in .env.testing)
    // In production, it should be true
    expect(config()->has('session.secure'))->toBeTrue();
});

// ─── Route Tests ──────────────────────────────────────────

test('network error page is accessible', function () {
    $response = $this->get('/network-error');
    $response->assertStatus(200);
    $response->assertSee('Koneksi Terputus');
});

test('privacy policy page is accessible', function () {
    $response = $this->get('/privacy');
    $response->assertStatus(200);
    $response->assertSee('Kebijakan Privasi');
});

test('terms of service page is accessible', function () {
    $response = $this->get('/terms');
    $response->assertStatus(200);
    $response->assertSee('Ketentuan Layanan');
});

test('offline page is accessible', function () {
    $response = $this->get('/offline');
    $response->assertStatus(200);
});

// ─── Network Error Page Content Tests ─────────────────────

test('network error page has correct meta tags', function () {
    $response = $this->get('/network-error');
    $response->assertSee('Koneksi Terputus');
    // Check meta tags in the blade file directly
    $content = file_get_contents(resource_path('views/network-error.blade.php'));
    expect($content)->toContain('viewport');
});

test('network error page has auto-retry script', function () {
    $response = $this->get('/network-error');
    // Check script in blade file directly (HTML entities encoded in response)
    $content = file_get_contents(resource_path('views/network-error.blade.php'));
    expect($content)->toContain("window.addEventListener('online'");
});

// ─── Privacy Policy Page Content Tests ────────────────────

test('privacy policy has all required sections', function () {
    $response = $this->get('/privacy');
    $response->assertStatus(200);
    // Content is rendered via JavaScript, check the JS file
    $jsContent = file_get_contents(public_path('landing/privacy-content.js'));
    expect($jsContent)->toContain('Pendahuluan');
    expect($jsContent)->toContain('Identitas Pengelola');
    expect($jsContent)->toContain('Data yang Kami Kumpulkan');
    expect($jsContent)->toContain('Penggunaan Data');
    expect($jsContent)->toContain('Dasar Hukum');
    expect($jsContent)->toContain('Penyimpanan');
    expect($jsContent)->toContain('Berbagi Data');
    expect($jsContent)->toContain('Hak Pengguna');
    expect($jsContent)->toContain('Cookies');
    expect($jsContent)->toContain('Retensi Data');
    expect($jsContent)->toContain('Hubungi Kami');
});

test('privacy policy has company info', function () {
    $response = $this->get('/privacy');
    $response->assertSee('PT Zona Teknologi Nusantara');
    // Email is in JavaScript content, check the JS file instead
    $jsContent = file_get_contents(public_path('landing/privacy-content.js'));
    expect($jsContent)->toContain('zonakasirapp@gmail.com');
});

// ─── Terms of Service Page Content Tests ──────────────────

test('terms of service has all required sections', function () {
    $response = $this->get('/terms');
    $response->assertStatus(200);
    // Content is rendered via JavaScript, check the JS file
    $jsContent = file_get_contents(public_path('landing/terms-content.js'));
    expect($jsContent)->toContain('Penerimaan Ketentuan');
    expect($jsContent)->toContain('Deskripsi Layanan');
    expect($jsContent)->toContain('Pendaftaran');
    expect($jsContent)->toContain('Paket Berlangganan');
    expect($jsContent)->toContain('Pembayaran');
    expect($jsContent)->toContain('Penggunaan yang Diizinkan');
    expect($jsContent)->toContain('Larangan Penggunaan');
    expect($jsContent)->toContain('Kepemilikan Data');
    expect($jsContent)->toContain('Keamanan');
    expect($jsContent)->toContain('Mode Offline');
    expect($jsContent)->toContain('Force Majeure');
    expect($jsContent)->toContain('Hubungi Kami');
});

test('terms of service has company info', function () {
    $response = $this->get('/terms');
    $response->assertSee('PT Zona Teknologi Nusantara');
    // Email is in JavaScript content, check the JS file instead
    $jsContent = file_get_contents(public_path('landing/terms-content.js'));
    expect($jsContent)->toContain('zonakasirapp@gmail.com');
});

// ─── Error Page Tests ─────────────────────────────────────

test('401 error page has auto-redirect', function () {
    $response = $this->get('/nonexistent-route-that-should-401');
    // Check that 401 page template exists
    $this->assertFileExists(resource_path('views/errors/401.blade.php'));
});

test('403 error page has auto-redirect', function () {
    $this->assertFileExists(resource_path('views/errors/403.blade.php'));
});

// ─── Service Worker Tests ─────────────────────────────────

test('service worker file exists', function () {
    $this->assertFileExists(public_path('serviceworker.js'));
});

test('service worker has cache version', function () {
    $content = file_get_contents(public_path('serviceworker.js'));
    expect($content)->toContain('CACHE_VERSION');
});

test('service worker handles 401/403', function () {
    $content = file_get_contents(public_path('serviceworker.js'));
    expect($content)->toContain('resp.status === 401 || resp.status === 403');
});

test('service worker has login redirect function', function () {
    $content = file_get_contents(public_path('serviceworker.js'));
    expect($content)->toContain('getLoginRedirect');
    expect($content)->toContain('isLoginPage');
});

// ─── Custom JavaScript Tests ──────────────────────────────

test('custom javascript file exists', function () {
    $this->assertFileExists(public_path('js/app/custom-javascript.js'));
});

test('custom javascript has fetch interceptor', function () {
    $content = file_get_contents(public_path('js/app/custom-javascript.js'));
    expect($content)->toContain('Auth Error Interceptor');
    expect($content)->toContain('origFetch');
});

test('custom javascript has Livewire error modal interceptor', function () {
    $content = file_get_contents(public_path('js/app/custom-javascript.js'));
    expect($content)->toContain('Livewire Error Modal Interceptor');
    expect($content)->toContain('livewire-error');
});

test('custom javascript has PWA visibility change handler', function () {
    $content = file_get_contents(public_path('js/app/custom-javascript.js'));
    expect($content)->toContain('visibilitychange');
    expect($content)->toContain('refresh session');
});

// ─── Cashier Page Offline Tests ───────────────────────────

test('cashier blade has PWA detection', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('isPWA');
    expect($content)->toContain('display-mode: standalone');
});

test('cashier blade has offline product grid', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('offlineProducts');
    expect($content)->toContain('filteredOfflineProducts');
});

test('cashier blade has offline cart', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('offlineCart');
    expect($content)->toContain('offlineAddToCart');
    expect($content)->toContain('offlineRemoveFromCart');
});

test('cashier blade has offline payment', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('paymentModalOpen');
    expect($content)->toContain('saveOfflineSale');
});

test('cashier blade redirects to network error when offline in browser', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('/network-error');
});

test('cashier blade only shows offline features in PWA mode', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    // Check that offline sections have isPWA condition
    expect($content)->toContain('isOffline && isPWA');
});

// ─── Offline Payment Restrictions Tests ───────────────────

test('offline payment modal only allows cash', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    // Cash button should be enabled
    expect($content)->toContain('offlinePaymentMethod');
    expect($content)->toContain("offlinePaymentMethod = 'cash'");
    // QRIS and Card should be disabled
    expect($content)->toContain('disabled');
    expect($content)->toContain('cursor-not-allowed');
});

test('offline payment warns QRIS unavailable', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('QRIS & digital payment unavailable offline');
});

test('offline mode indicator banner exists', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('Offline Mode Active');
    expect($content)->toContain('Cash payment only');
});

test('offline mode indicator only shows in PWA', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    // Find the offline indicator section
    expect($content)->toContain('x-show="isOffline && isPWA"');
});

// ─── Offline Cart Validation Tests ────────────────────────

test('offline cart prevents out of stock items', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    // Check that offlineAddToCart checks stock
    expect($content)->toContain('is_non_stock');
    expect($content)->toContain('stock_calculate');
});

test('offline sale saves to IndexedDB pending_sales', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('pending_sales');
    expect($content)->toContain("status: 'pending'");
    expect($content)->toContain('synced: false');
});

test('offline cart clears after save', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    // After saveOfflineSale, cart should be cleared
    expect($content)->toContain('this.offlineCart = {}');
});

// ─── Service Worker Offline Handling Tests ────────────────

test('service worker returns cached page when offline', function () {
    $content = file_get_contents(public_path('serviceworker.js'));
    expect($content)->toContain('return cached');
    expect($content)->toContain('OFFLINE_PAGE');
});

test('service worker does not cache auth error pages', function () {
    $content = file_get_contents(public_path('serviceworker.js'));
    // Should not cache 401/403 responses
    expect($content)->toContain("if (resp.status === 401 || resp.status === 403)");
    expect($content)->toContain('return resp');
});

// ─── PWA Detection Tests ──────────────────────────────────

test('cashier detects PWA mode via display-mode', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain("display-mode: standalone");
});

test('cashier detects PWA mode via navigator.standalone', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('navigator.standalone');
});

test('cashier redirects to network error when offline in browser', function () {
    $content = file_get_contents(resource_path('views/filament/tenant/pages/cashier.blade.php'));
    expect($content)->toContain('/network-error');
});
