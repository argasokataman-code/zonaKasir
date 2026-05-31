<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenants\StockOpname;
use App\Models\Tenants\StockOpnameItem;

$so = StockOpname::orderBy('id', 'desc')->first();
if ($so) {
    echo "StockOpname:\n";
    echo json_encode($so->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    $items = StockOpnameItem::where('stock_opname_id', $so->id)->get();
    echo "Items:\n";
    echo json_encode($items->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo "No StockOpname found\n";
}
