<?php

/**
 * Validation Rules Configuration
 * 
 * Centralizes validation rules to avoid hard-coded values throughout the codebase
 */

return [
    /**
     * Phone number validation rules by locale
     * Format: 'locale' => ['min' => min_digits, 'max' => max_digits]
     */
    'phone' => [
        'id' => ['min' => 10, 'max' => 13],  // Indonesia: 0812-9999-9999
        'default' => ['min' => 7, 'max' => 15],  // International standard
    ],

    /**
     * Email validation configuration
     */
    'email' => [
        'max_length' => 255,
    ],

    /**
     * Password validation configuration
     */
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_numbers' => true,
        'require_special_chars' => false,
    ],

    /**
     * Product validation
     */
    'product' => [
        'sku_max_length' => 50,
        'barcode_max_length' => 128,
        'name_max_length' => 255,
    ],
];
