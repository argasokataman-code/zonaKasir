<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenants\Product;
use Illuminate\Support\Facades\DB;

$products = Product::select('id', 'name', 'sku', 'selling_price', 'is_non_stock', 'category_id', 'hero_images')
    ->with(['stocks' => fn($q) => $q->select('product_id', 'stock', 'type', 'initial_price', 'selling_price', 'date', 'created_at')->where('is_ready', 1)->where('type', 'in'), 'category:id,name'])
    ->where('show', true)
    ->take(100)
    ->get();

// Simulate what Livewire would send back as component HTML
$html = '';
foreach ($products as $product) {
    $html .= "<div class='product-card' wire:key='product-{$product->id}' data-id='{$product->id}'>";
    $html .= "<div class='product-image'>{$product->heroImage}</div>";
    $html .= "<div class='product-name'>{$product->name}</div>";
    $html .= "<div class='product-price'>{$product->sellingPriceCalculate}</div>";
    $html .= "<div class='product-stock'>{$product->stockCalculate}</div>";
    $html .= "<button wire:click='addCart({$product->id})'>+</button>";
    $html .= "</div>";
}

echo "Product grid HTML size: " . strlen($html) . " bytes (" . round(strlen($html)/1024, 1) . " KB)" . PHP_EOL;
echo "Estimated Livewire JSON payload: " . round(strlen($html) * 1.5 / 1024, 1) . " KB" . PHP_EOL;
