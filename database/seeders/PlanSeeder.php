<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(['slug' => 'lite'], [
            'name' => 'Paket Lite',
            'slug' => 'lite',
            'price_monthly' => 0,
            'price_yearly' => null,
            'features' => [
                'pos',
                'print_selling_a5',
                'print_product_label',
                'payment_shortcut',
                'edit_profile',
                'total_revenue',
            ],
            'max_stores' => 1,
            'max_users' => 1,
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['slug' => 'pro'], [
            'name' => 'Paket Pro',
            'slug' => 'pro',
            'price_monthly' => 149000,
            'price_yearly' => 1190000,
            'features' => [
                'pos',
                'report',
                'stock_management',
                'member_management',
                'voucher',
                'purchasing',
                'receivable',
                'supplier',
                'export_csv',
                'custom_print',
                'product_import',
                'product_barcode',
                'product_sku',
                'product_expired',
                'product_type',
                'product_initial_price',
                'selling_tax',
                'payment_shortcut',
                'total_revenue',
                'edit_profile',
                'print_selling_a5',
                'print_product_label',
            ],
            'max_stores' => 3,
            'max_users' => 5,
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['slug' => 'enterprise'], [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price_monthly' => 299000,
            'price_yearly' => 2390000,
            'features' => [
                'pos',
                'report',
                'stock_management',
                'stock_opname',
                'multi_store',
                'api_access',
                'export_csv',
                'custom_print',
                'product_import',
                'purchasing',
                'receivable',
                'supplier',
                'voucher',
                'user_management',
                'role_permission',
                'product_barcode',
                'product_sku',
                'product_expired',
                'product_type',
                'product_initial_price',
                'selling_tax',
                'print_selling_a5',
                'print_product_label',
                'payment_shortcut',
                'total_revenue',
                'edit_profile',
            ],
            'max_stores' => 99,
            'max_users' => 99,
            'is_active' => true,
        ]);

        Plan::updateOrCreate(['slug' => 'on-premise'], [
            'name' => 'On-Premise',
            'slug' => 'on-premise',
            'price_monthly' => 0,
            'price_yearly' => null,
            'features' => [
                'pos',
                'report',
                'stock_management',
                'stock_opname',
                'multi_store',
                'api_access',
                'export_csv',
                'custom_print',
                'product_import',
                'purchasing',
                'receivable',
                'supplier',
                'voucher',
                'user_management',
                'role_permission',
                'product_barcode',
                'product_sku',
                'product_expired',
                'product_type',
                'product_initial_price',
                'selling_tax',
                'print_selling_a5',
                'print_product_label',
                'payment_shortcut',
                'total_revenue',
                'edit_profile',
            ],
            'max_stores' => 99,
            'max_users' => 99,
            'is_active' => true,
        ]);
    }
}
