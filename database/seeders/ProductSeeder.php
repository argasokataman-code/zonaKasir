<?php

namespace Database\Seeders;

use App\Models\Tenants\Product;
use App\Models\Tenants\Stock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL DISABLE');
        }

        Product::truncate();

        $products = [
            [
                'category_id' => 1,
                'name' => 'Royal Canin Adult Dog Food 3kg',
                'sku' => 'RC-DOG-3KG',
                'stock' => 20,
                'initial_price' => 200000,
                'selling_price' => 250000,
                'unit' => 'PCS',
                'type' => 'product',
                'show' => true,
                'hero_images' => [
                    'https://picsum.photos/seed/royalcanin/800/600',
                ],
            ],
            [
                'category_id' => 1,
                'name' => 'Whiskas Adult Cat Food 1.2kg',
                'sku' => 'WH-CAT-1.2KG',
                'stock' => 30,
                'initial_price' => 40000,
                'selling_price' => 55000,
                'unit' => 'PCS',
                'type' => 'product',
                'show' => true,
                'hero_images' => [
                    'https://picsum.photos/seed/whiskas/800/600',
                ],
            ],
            [
                'category_id' => 1,
                'name' => 'Dog Shampoo 500ml',
                'sku' => 'DOG-SHAMPOO-500',
                'stock' => 50,
                'initial_price' => 30000,
                'selling_price' => 45000,
                'unit' => 'PCS',
                'type' => 'product',
                'show' => true,
                'hero_images' => [
                    'https://picsum.photos/seed/dogshampoo/800/600',
                ],
            ],
            [
                'category_id' => 1,
                'name' => 'Cat Litter 5kg',
                'sku' => 'CAT-LITTER-5KG',
                'stock' => 15,
                'initial_price' => 60000,
                'selling_price' => 85000,
                'unit' => 'PCS',
                'type' => 'product',
                'show' => true,
                'hero_images' => [
                    'https://picsum.photos/seed/catlitter/800/600',
                ],
            ],
            [
                'category_id' => 1,
                'name' => 'Bird Seed Mix 1kg',
                'sku' => 'BIRD-SEED-1KG',
                'stock' => 40,
                'initial_price' => 15000,
                'selling_price' => 22000,
                'unit' => 'PCS',
                'type' => 'product',
                'show' => true,
                'hero_images' => [
                    'https://picsum.photos/seed/birdseed/800/600',
                ],
            ],
        ];

        foreach ($products as $p) {
            $product = Product::create($p);
            Stock::create([
                'initial_price' => $p['initial_price'],
                'selling_price' => $p['selling_price'],
                'stock' => $p['stock'],
                'init_stock' => $p['stock'],
                'product_id' => $product->id,
                'type' => 'in',
            ]);
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'pgsql') {
            DB::statement('SET CONSTRAINTS ALL ENABLE');
        }
    }
}
