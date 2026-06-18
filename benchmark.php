<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenants\Product;
use Illuminate\Support\Facades\DB;

// Mock 100 produk
echo "Generating 100 products..." . PHP_EOL;
// (Mocking setup)
$start = microtime(true);
$products = Product::select('id', 'name', 'selling_price', 'is_non_stock', 'category_id', 'hero_images')
    ->with(['stocks' => fn($q) => $q->select('product_id', 'stock', 'type')->where('type', 'in')])
    ->take(100)
    ->get();

// Simulate Blade rendering loop (the Diffing bottleneck)
echo "Simulating render loop for 100 products..." . PHP_EOL;
$output = '';
foreach ($products as $product) {
    $stock = $product->stock_calculate; // Accessor
    $hero = $product->heroImage;        // Accessor
    $price = $product->sellingPriceCalculate; // Accessor
    $output .= "<div>{$product->name} - {$stock} - {$price}</div>";
}

$end = microtime(true);
echo "Render simulation took: " . round(($end - $start) * 1000, 2) . "ms" . PHP_EOL;
