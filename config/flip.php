<?php

return [
    'secret_key' => env('FLIP_SECRET_KEY'),
    'webhook_token' => env('FLIP_WEBHOOK_TOKEN'),
    'base_url' => env('FLIP_BASE_URL', 'https://big.flip.id/api/v2'),
];
