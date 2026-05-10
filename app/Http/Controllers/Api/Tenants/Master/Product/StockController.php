<?php

namespace App\Http\Controllers\Api\Tenants\Master\Product;

use App\Events\RecalculateEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Master\StockRequest;
use App\Http\Resources\StockCollection;
use App\Models\Tenants\Product;
use App\Models\Tenants\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Product $product, Request $request)
    {
        $perPage = $this->resolvePerPage($request);

        $stocks = $product->stocks()
            ->orderByDesc('created_at')
            ->simplePaginate($perPage);

        return $this->buildResponse()
            ->setData(StockCollection::collection($stocks))
            ->present();
    }

    private function resolvePerPage(Request $request): ?int
    {
        if (! $request->has('per_page')) {
            return null;
        }

        $perPage = filter_var($request->query('per_page'), FILTER_VALIDATE_INT);

        if ($perPage === false) {
            return null;
        }

        return max(1, min($perPage, 100));
    }

    public function store(StockRequest $request, Product $product)
    {
        $request->store();

        RecalculateEvent::dispatch(collect([$product]), []);

        return $this->buildResponse()
            ->setMessage('success creating stock for ' . $product->name)
            ->present();
    }

    public function destroy(Product $product, Stock $stock)
    {
        abort_unless($stock->product_id === $product->id, 404);

        $stock->delete();

        RecalculateEvent::dispatch(collect([$product]), []);

        return $this->buildResponse()
            ->setMessage('success deleting stock for ' . $product->name)
            ->present();
    }
}
