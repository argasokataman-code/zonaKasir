<?php

/**
 * Niche-based navigation visibility.
 *
 * Keys = business_type values from About model.
 * Values = array of nav item keys that are VISIBLE for that niche.
 * Items not listed here use default visibility (shown unless feature-gated).
 *
 * 'hidden' = nav items that are always hidden for this niche.
 * 'requires' = nav items that require these feature flags (extra check).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Navigation visibility per niche
    |--------------------------------------------------------------------------
    |
    | 'only' => only these nav items are shown (everything else hidden)
    | 'hidden' => these nav items are hidden (everything else shown)
    | 'requires' => these nav items need extra feature flags beyond default
    |
    | Nav item keys match the slug used in TenantPanelProvider.
    |
    */
    'nav' => [
        'fnb' => [
            'hidden' => ['marketing_content'],
            'requires' => [],
        ],

        'retail' => [
            'hidden' => ['kitchen_display', 'table'],
            'requires' => [],
        ],

        'wholesale' => [
            'hidden' => ['kitchen_display', 'table'],
            'requires' => [],
        ],

        'fashion' => [
            'hidden' => ['kitchen_display', 'table'],
            'requires' => [],
        ],

        'pharmacy' => [
            'hidden' => ['kitchen_display', 'table'],
            'requires' => [],
        ],

        'other' => [
            'hidden' => ['kitchen_display', 'table', 'marketing_content'],
            'requires' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default primary colors per niche
    |--------------------------------------------------------------------------
    */
    'default_colors' => [
        'fnb' => '#E65100',
        'retail' => '#1565C0',
        'wholesale' => '#2E7D32',
        'fashion' => '#AD1457',
        'pharmacy' => '#00838F',
        'other' => '#FF6600',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default categories per niche (for onboarding seeder)
    |--------------------------------------------------------------------------
    */
    'default_categories' => [
        'fnb' => ['Makanan', 'Minuman', 'Snack', 'Topping', 'Paket'],
        'retail' => ['Elektronik', 'Pakaian', 'Aksesoris', 'Perlengkapan Rumah'],
        'wholesale' => ['Sembako', 'Minuman', 'Produk Rumah Tangga'],
        'fashion' => ['Pria', 'Wanita', 'Anak', 'Aksesoris'],
        'pharmacy' => ['Obat', 'Vitamin', 'Alat Kesehatan', 'Kecantikan'],
        'other' => ['Umum'],
    ],
];
