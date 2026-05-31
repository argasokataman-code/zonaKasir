<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Tenants\StockOpnameItem;
$id = $argv[1] ?? null;
if (!$id) { echo "Usage: php scripts/delete_item.php {id}\n"; exit(1); }
$item = StockOpnameItem::find($id);
if (!$item) { echo "Item {$id} not found\n"; exit(0); }
$item->delete();
echo "Deleted item {$id}\n";
