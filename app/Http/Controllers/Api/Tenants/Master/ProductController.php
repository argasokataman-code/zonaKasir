<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Filters\ComparisonFilter;
use App\Http\Controllers\Controller;
use App\Http\Filters\SearchFields;
use App\Http\Requests\Tenants\Master\ProductRequest;
use App\Http\Resources\ProductCollection;
use App\Models\Tenants\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->resolvePerPage($request);

        $products = QueryBuilder::for(Product::class)
            ->select('id', 'name', 'sku', 'selling_price', 'initial_price', 'type', 'unit', 'stock', 'is_non_stock', 'category_id', 'hero_images', 'show', 'created_at')
            ->allowedFilters([
                'name',
                'category_id',
                'sellingPrice',
                'initialPrice',
                'type',
                'category.name',
                'unit',
                'show',
                ...ComparisonFilter::setFilters('stock', ['gt', 'ge', 'lt', 'le', 'eq', 'ne']),
                AllowedFilter::custom('global', new SearchFields, 'name,sku,barcodes.code'),
            ])
            ->allowedIncludes(['category', 'images'])
            ->with(['category:id,name', 'stocks:product_id,stock,type,is_ready,date,created_at', 'primaryBarcode'])
            ->orderByDesc('created_at')
            ->simplePaginate($perPage ?? (new Product())->getPerPage());

        return $this->buildResponse()
            ->setData(ProductCollection::collection($products))
            ->present();
    }

    public function store(ProductRequest $request): JsonResponse
    {
        try {
            $request->created();
            $product = Product::latest()->first();
            $product->load(['category:id,name', 'stocks:product_id,stock,type,is_ready,date,created_at', 'primaryBarcode']);

            return $this->buildResponse()
                ->setData(new ProductCollection($product))
                ->setCode(201)
                ->setMessage('Product created successfully')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to create product: ' . $e->getMessage())
                ->present();
        }
    }

    public function show($product): JsonResponse
    {
        $modelById = is_numeric($product)
            ? Product::select('id', 'name', 'sku', 'selling_price', 'initial_price', 'type', 'unit', 'stock', 'is_non_stock', 'category_id', 'hero_images', 'show')->find($product)
            : null;
        $modelByCode = Product::findByBarcodeOrSku($product);

        if ($modelById && $modelByCode && $modelById->getKey() !== $modelByCode->getKey()) {
            abort(409, 'Ambiguous product identifier');
        }

        $model = $modelById ?? $modelByCode;
        if (! $model) {
            abort(404, 'Product not found');
        }

        $model->load(['category:id,name', 'stocks:product_id,stock,type,is_ready,date,created_at', 'primaryBarcode']);
        $model = new ProductCollection($model);

        return $this->buildResponse()
            ->setData($model)
            ->present();
    }

    public function update(ProductRequest $request): JsonResponse
    {
        try {
            $request->updated();
            $product = Product::findorfail($request->route('product'));
            $product->load(['category:id,name', 'stocks:product_id,stock,type,is_ready,date,created_at', 'primaryBarcode']);

            return $this->buildResponse()
                ->setData(new ProductCollection($product))
                ->setMessage('Product updated successfully')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to update product: ' . $e->getMessage())
                ->present();
        }
    }

    public function destroy(Product $product, ProductRequest $request): JsonResponse
    {
        $request->deleteImages();
        $product->delete();

        return $this->buildResponse()
            ->setMessage('Product deleted successfully')
            ->present();
    }
}
