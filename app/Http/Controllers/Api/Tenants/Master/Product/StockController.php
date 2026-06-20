<?php

namespace App\Http\Controllers\Api\Tenants\Master\Product;

use App\Events\RecalculateEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Master\StockRequest;
use App\Http\Resources\StockCollection;
use App\Models\Tenants\Product;
use App\Models\Tenants\Stock;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Product $product, Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $stocks = $product->stocks()
            ->with('product')
            ->orderByDesc('created_at')
            ->simplePaginate($perPage ?? (new Stock())->getPerPage());

        return $this->buildResponse()
            ->setData(StockCollection::collection($stocks))
            ->present();
    }

    public function store(StockRequest $request, Product $product): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->store();

            DB::commit();

            RecalculateEvent::dispatch(collect([$product]), []);

            return $this->buildResponse()
                ->setMessage('success creating stock for ' . $product->name)
                ->present();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to create stock: ' . $e->getMessage())
                ->present();
        }
    }

    public function destroy(Product $product, Stock $stock): JsonResponse
    {
        abort_unless((int) $stock->product_id === $product->id, 404);

        try {
            DB::beginTransaction();

            $stock->delete();

            DB::commit();

            RecalculateEvent::dispatch(collect([$product]), []);

            return $this->buildResponse()
                ->setMessage('success deleting stock for ' . $product->name)
                ->present();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to delete stock: ' . $e->getMessage())
                ->present();
        }
    }
}
