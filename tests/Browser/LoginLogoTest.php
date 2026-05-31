<?php

use Laravel\Dusk\Browser;

test('login page brand logo uses absolute storage URL', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/member/login')
            ->waitFor('img[alt="ZONAPIE PETSHOP logo"]')
            ->assertVisible('img[alt="ZONAPIE PETSHOP logo"]');

        $logoSrc = $browser->script('return document.querySelector(\'img[alt="ZONAPIE PETSHOP logo"]\')?.src;')[0] ?? null;

        expect($logoSrc)->not->toBeNull();
        expect($logoSrc)->toContain('http');
        expect($logoSrc)->not->toContain('/member/');
        expect($logoSrc)->toContain('/storage/profile/');
    });
});
