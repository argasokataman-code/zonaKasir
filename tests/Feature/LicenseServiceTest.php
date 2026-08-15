<?php

use App\Services\LicenseService;

$keypair = function () {
    $res = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    openssl_pkey_export($res, $private);
    $details = openssl_pkey_get_details($res);

    return [$private, $details['key']];
};

it('signs and verifies a valid license', function () use ($keypair) {
    [$private, $public] = $keypair();

    $license = app(LicenseService::class)->sign([
        'customer' => 'Toko Budi',
        'domain' => 'tokobudi.zona.id',
        'expires_at' => now()->addDays(30)->toDateString(),
    ], $private);

    $result = app(LicenseService::class)->verify("ZONA-TOKO-BUDI-{$license}", 'tokobudi.zona.id', $public);

    expect($result['valid'])->toBeTrue();
    expect($result['payload']['customer'])->toBe('Toko Budi');
});

it('rejects license bound to another domain', function () use ($keypair) {
    [$private, $public] = $keypair();

    $license = app(LicenseService::class)->sign([
        'customer' => 'Toko Budi',
        'domain' => 'tokobudi.zona.id',
        'expires_at' => now()->addDays(30)->toDateString(),
    ], $private);

    $result = app(LicenseService::class)->verify("ZONA-TOKO-BUDI-{$license}", 'lain.zona.id', $public);

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('License is bound to another domain');
});

it('rejects expired license', function () use ($keypair) {
    [$private, $public] = $keypair();

    $license = app(LicenseService::class)->sign([
        'customer' => 'Toko Budi',
        'domain' => 'tokobudi.zona.id',
        'expires_at' => now()->subDays(1)->toDateString(),
    ], $private);

    $result = app(LicenseService::class)->verify("ZONA-TOKO-BUDI-{$license}", 'tokobudi.zona.id', $public);

    expect($result['valid'])->toBeFalse();
    expect($result['reason'])->toBe('License expired');
});

it('rejects tampered license', function () use ($keypair) {
    [$private, $public] = $keypair();

    $license = app(LicenseService::class)->sign([
        'customer' => 'Toko Budi',
        'domain' => 'tokobudi.zona.id',
        'expires_at' => now()->addDays(30)->toDateString(),
    ], $private);

    $tampered = substr("ZONA-TOKO-BUDI-{$license}", 0, -4).'AAAA';

    $result = app(LicenseService::class)->verify($tampered, 'tokobudi.zona.id', $public);

    expect($result['valid'])->toBeFalse();
});
