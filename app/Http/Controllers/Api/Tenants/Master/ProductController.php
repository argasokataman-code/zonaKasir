<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Filters\ComparisonFilter;
use App\Http\Controllers\Controller;
use App\Http\Filters\SearchFields;
use App\Http\Requests\Tenants\Master\ProductRequest;
use App\Http\Resources\ProductCollection;
use App\Models\Tenants\Product;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $this->resolvePerPage($request);

        $products = QueryBuilder::for(Product::class)
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
                AllowedFilter::custom('global', new SearchFields, 'name,sku,barcode'),
            ])
            ->allowedIncludes(['category', 'images'])
            ->orderByDesc('created_at')
            ->simplePaginate($perPage);

        return $this->buildResponse()
            ->setData(ProductCollection::collection($products))
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

    public function store(ProductRequest $request)
    {
        $request->created();

        return $this->buildResponse()
            ->setMessage('success creating items')
            ->present();
    }

    public function show(Product $product)
    {
        $product->load(['category', 'stocks']);
        $product = new ProductCollection($product);

        return $this->buildResponse()
            ->setData($product)
            ->present();
    }

    public function update(ProductRequest $request)
    {
        $request->updated();

        return $this->buildResponse()
            ->setMessage('success updating items')
            ->present();
    }

    public function destroy(Product $product, ProductRequest $request)
    {
        $request->deleteImages();
        $product->delete();

        return $this->buildResponse()
            ->setMessage('success deleting items')
            ->present();
    }
}
