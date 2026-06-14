<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Site key and secret key for Cloudflare Turnstile CAPTCHA.
    | Get keys at https://dash.cloudflare.com/turnstile
    |
    | site_key: Public key (used in frontend widget)
    | secret_key: Private key (used in server-side validation)
    | enabled: Master switch to enable/disable Turnstile validation
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY', ''),
    'secret_key' => env('TURNSTILE_SECRET_KEY', ''),
    'enabled' => env('TURNSTILE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Siteverify URL
    |--------------------------------------------------------------------------
    |
    | Cloudflare's token verification endpoint.
    |
    */

    'siteverify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | HTTP timeout in seconds for the siteverify API call.
    |
    */

    'timeout' => 10,

];
