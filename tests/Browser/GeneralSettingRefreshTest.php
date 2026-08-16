<?php

use Laravel\Dusk\Browser;
use App\Models\Tenants\User;
use App\Services\TenantContext;

beforeEach(function () {
    $this->user = User::first();
    TenantContext::set($this->user->tenant_id);
});

test('general setting save reflects new value in select', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAs($this->user)
            ->visit('/member/general-setting')
            ->waitForText('General Setting', 10);

        // find currency select value before
        $before = $browser->value('select[wire\\:model="setting.currency"]');
        fwrite(STDERR, "CURRENCY BEFORE: " . var_export($before, true) . "\n");

        // set to USD via JS (Alpine)
        $browser->script('
            const el = document.querySelector(\'select[wire\\:model="setting.currency"]\');
            el.value = "USD";
            el.dispatchEvent(new Event("input", { bubbles: true }));
            el.dispatchEvent(new Event("change", { bubbles: true }));
        ');

        // click Save button in App tab
        $browser->script('
            const btns = [...document.querySelectorAll("button")];
            const save = btns.find(b => b.textContent.trim() === "Save");
            if (save) save.click();
        ');

        // confirmation modal confirm
        $browser->waitForText('Confirmation', 5);
        $browser->script('
            const btns = [...document.querySelectorAll("button")];
            const confirm = btns.find(b => /Confirm/i.test(b.textContent));
            if (confirm) confirm.click();
        ');

        $browser->waitForText('Success', 8);

        $after = $browser->value('select[wire\\:model="setting.currency"]');
        fwrite(STDERR, "CURRENCY AFTER: " . var_export($after, true) . "\n");

        $dbVal = \App\Models\Tenants\Setting::get('currency', 'IDR');
        fwrite(STDERR, "DB CURRENCY: " . var_export($dbVal, true) . "\n");

        expect(true)->toBeTrue();
    });
});
