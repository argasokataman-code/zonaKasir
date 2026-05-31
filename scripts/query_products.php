<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use App\Models\Tenants\Product;
$products = Product::limit(20)->get(['id','name','sku']);
foreach($products as $p){ echo "{$p->id}\t{$p->sku}\t{$p->name}\n"; }
if($products->isEmpty()) echo "No products\n";
