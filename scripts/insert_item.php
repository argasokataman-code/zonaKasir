<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Tenants\StockOpnameItem;
use App\Models\Tenants\Product;

$stockOpnameId = $argv[1] ?? 5;
$productId = $argv[2] ?? 1;
$adjustmentType = 'Manual Input';
$currentStock = 10;
$amount = 5;
$amountAfter = $currentStock - $amount;

$item = StockOpnameItem::create([
    'product_id' => $productId,
    'stock_opname_id' => $stockOpnameId,
    'adjustment_type' => $adjustmentType,
    'current_stock' => $currentStock,
    'amount' => $amount,
    'amount_after_adjustment' => $amountAfter,
]);

echo "Inserted item id: {$item->id}\n";
