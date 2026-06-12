<?php

use App\Services\Tenants\MidtransFeeCalculator;
use App\Services\Tenants\UnknownPaymentTypeException;

test('fee calculator: credit card percentage', function () {
    $fees = (new MidtransFeeCalculator)->calculate('credit_card', 100000, 1.0);
    expect($fees['fee_midtrans'])->toBe(2950.0);   // 2.95%
    expect($fees['fee_platform'])->toBe(1000.0);    // 1%
    expect($fees['net_amount'])->toBe(96050.0);     // 100000 - 2950 - 1000
    expect($fees['fee_midtrans_rate_type'])->toBe('percentage');
    expect($fees['fee_midtrans_rate_value'])->toBe(2.95);
});

test('fee calculator: bank transfer flat', function () {
    $fees = (new MidtransFeeCalculator)->calculate('bank_transfer', 50000, 1.0);
    expect($fees['fee_midtrans'])->toBe(2500.0);    // flat 2500
    expect($fees['net_amount'])->toBe(47000.0);     // 50000 - 2500 - 500
    expect($fees['fee_midtrans_rate_type'])->toBe('flat');
    expect($fees['fee_midtrans_rate_value'])->toBe(2500.0);
});

test('fee calculator: gopay percentage', function () {
    $fees = (new MidtransFeeCalculator)->calculate('gopay', 200000, 1.5);
    expect($fees['fee_midtrans'])->toBe(3000.0);     // 1.5%
    expect($fees['fee_platform'])->toBe(3000.0);     // 1.5%
    expect($fees['net_amount'])->toBe(194000.0);     // 200000 - 3000 - 3000
});

test('fee calculator: qris percentage', function () {
    $fees = (new MidtransFeeCalculator)->calculate('qris', 100000, 1.0);
    expect($fees['fee_midtrans'])->toBe(700.0);      // 0.7%
    expect($fees['fee_platform'])->toBe(1000.0);     // 1%
    expect($fees['net_amount'])->toBe(98300.0);      // 100000 - 700 - 1000
});

test('fee calculator throws on unknown type', function () {
    try {
        (new MidtransFeeCalculator)->calculate('unknown_method', 50000, 1.0);
        $this->fail('Expected exception was not thrown');
    } catch (UnknownPaymentTypeException $e) {
        $this->assertStringContainsString('Unknown payment type: unknown_method', $e->getMessage());
    }
});

test('unknown payment type exception class exists', function () {
    expect(class_exists(UnknownPaymentTypeException::class))->toBeTrue();
});

test('fee calculator: indomaret flat fee', function () {
    $fees = (new MidtransFeeCalculator)->calculate('indomaret', 30000, 1.0);
    expect($fees['fee_midtrans'])->toBe(2500.0);     // flat 2500
    expect($fees['fee_platform'])->toBe(300.0);      // 1%
    expect($fees['net_amount'])->toBe(27200.0);      // 30000 - 2500 - 300
});

test('fee calculator: zero platform fee', function () {
    $fees = (new MidtransFeeCalculator)->calculate('credit_card', 100000, 0);
    expect($fees['fee_platform'])->toBe(0.0);
    expect($fees['net_amount'])->toBe(97050.0);      // 100000 - 2950 - 0
});

test('fee calculator: kredivo percentage', function () {
    $fees = (new MidtransFeeCalculator)->calculate('kredivo', 100000, 1.0);
    expect($fees['fee_midtrans'])->toBe(3000.0);      // 3%
    expect($fees['net_amount'])->toBe(96000.0);       // 100000 - 3000 - 1000
});
