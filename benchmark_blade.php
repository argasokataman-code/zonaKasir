<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php');
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenants\Product;
use Illuminate\Support\Facades\View;

$products = Product::select('id', 'name', 'sku', 'selling_price', 'is_non_stock', 'category_id', 'hero_images')
    ->with(['stocks' => fn($q) => $q->select('product_id', 'stock', 'type', 'initial_price', 'selling_price', 'date', 'created_at')->where('is_ready', 1)->where('type', 'in'), 'category:id,name'])
    ->where('show', true)
    ->take(100)
    ->get();

// Simulate actual Blade rendering
$html = '';
foreach ($products as $product) {
    $html .= '<div class="group relative flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-all hover:shadow-md dark:border-gray-700 dark:bg-gray-800">';
    $html .= '<div class="relative aspect-[4/3] overflow-hidden bg-gray-100 dark:bg-gray-700">';
    $html .= '<img src="' . $product->heroImage . '" alt="' . $product->name . '" class="h-full w-full object-cover transition-transform group-hover:scale-105">';
    $html .= '<span class="absolute left-2 top-2 rounded-md px-1.5 py-0.5 text-xs font-bold text-white shadow-sm bg-zonakasir-primary">' . $product->stockCalculate . ' Stock</span>';
    $html .= '<span class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-zonakasir-primary text-xs font-bold text-white shadow-sm" x-text="cartQty[' . $product->id . ']"></span>';
    $html .= '</div>';
    $html .= '<div class="flex flex-1 flex-col justify-between p-3">';
    $html .= '<div><p class="text-xs font-medium text-gray-500 dark:text-gray-400">' . $product->sku . '</p>';
    $html .= '<h3 class="mt-0.5 text-sm font-semibold leading-tight text-gray-900 dark:text-white line-clamp-2">' . $product->name . '</h3></div>';
    $html .= '<div class="mt-2 flex items-center justify-between">';
    $html .= '<span class="text-sm font-bold text-zonakasir-primary">' . $product->sellingPriceCalculate . '</span>';
    $html .= '<button @click="instantAdd(' . $product->id . ')" class="flex min-h-[44px] min-w-[44px] items-center justify-center rounded-full bg-zonakasir-primary text-white">+</button>';
    $html .= '</div></div></div>';
}

echo "Actual product grid HTML: " . strlen($html) . " bytes (" . round(strlen($html)/1024, 1) . " KB)" . PHP_EOL;
echo "Per product: " . round(strlen($html)/100) . " bytes" . PHP_EOL;
echo "Estimated for 1000 products: " . round(strlen($html)/100 * 1000 / 1024, 1) . " KB" . PHP_EOL;
