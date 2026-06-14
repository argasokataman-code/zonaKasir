<?php

return [
    'name' => env('APP_NAME', 'zonaKasir'),
    'manifest' => [
        'name' => env('APP_NAME', 'zonaKasir POS'),
        'short_name' => mb_substr(env('APP_NAME', 'zonaKasir'), 0, 12),
        'start_url' => '/member',
        'background_color' => '#FF6600',
        'theme_color' => '#FF6600',
        'display' => 'standalone',
        'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
        'orientation' => 'any',
        'status_bar' => '#FF6600',
        'scope' => '/',
        'categories' => ['business', 'finance', 'point_of_sale'],
        'prefer_related_applications' => false,
        'icons' => [
            '48x48' => [
                'path' => '/images/icons/icon-48x48.png',
                'purpose' => 'any',
            ],
            '72x72' => [
                'path' => '/images/icons/icon-72x72.png',
                'purpose' => 'any',
            ],
            '96x96' => [
                'path' => '/images/icons/icon-96x96.png',
                'purpose' => 'any',
            ],
            '128x128' => [
                'path' => '/images/icons/icon-128x128.png',
                'purpose' => 'any',
            ],
            '144x144' => [
                'path' => '/images/icons/icon-144x144.png',
                'purpose' => 'any',
            ],
            '152x152' => [
                'path' => '/images/icons/icon-152x152.png',
                'purpose' => 'any',
            ],
            '192x192' => [
                'path' => '/images/icons/icon-192x192.png',
                'purpose' => 'any',
            ],
            '512x512' => [
                'path' => '/images/icons/icon-512x512.png',
                'purpose' => 'any',
            ],
        ],
        'shortcuts' => [
            [
                'name' => 'POS',
                'description' => 'POS transaction',
                'url' => '/member/cashier',
                'icons' => [
                    'src' => '/images/icons/icon-72x72.png',
                    'purpose' => 'any',
                ],
            ],
            [
                'name' => 'Product',
                'description' => 'Product List',
                'url' => '/member/products',
                'icons' => [
                    'src' => '/images/icons/icon-72x72.png',
                    'purpose' => 'any',
                ],
            ],
        ],
        'custom' => [],
    ],
];
