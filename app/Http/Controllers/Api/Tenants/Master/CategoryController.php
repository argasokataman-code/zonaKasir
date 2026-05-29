<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryCollection;
use App\Models\Tenants\Category;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = QueryBuilder::for(Category::class)
            ->allowedFilters(['name'])
            ->orderByDesc('created_at')
            ->get();

        return $this->buildResponse()
            ->setData(new CategoryCollection($categories))
            ->present();
    }

    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|unique:categories,name,NULL,id,tenant_id,'.tenant('id'),
        ]);
        
        try {
            DB::beginTransaction();
            
            $category = new Category();
            $category->fill($request->all());
            $category->save();
            
            DB::commit();
            
            return $this->buildResponse()
                ->setMessage('success creating category')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to create category: ' . $e->getMessage())
                ->present();
        }
    }

    public function show(Category $category): JsonResponse
    {
        return $this->buildResponse()
            ->setData(new CategoryCollection($category))
            ->present();
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $this->validate($request, [
            'name' => "required|unique:categories,name,{$category->id},id,tenant_id,".tenant('id'),
        ]);
        
        try {
            DB::beginTransaction();
            
            $category->fill($request->all());
            $category->save();
            
            DB::commit();
            
            return $this->buildResponse()
                ->setMessage('success updating category')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to update category: ' . $e->getMessage())
                ->present();
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->count() > 0) {
            return $this->buildResponse()
                ->setCode(400)
                ->setMessage('category has products')
                ->present();
        }
        
        try {
            DB::beginTransaction();
            $category->delete();
            DB::commit();

            return $this->buildResponse()
                ->setMessage('success deleting category')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to delete category: ' . $e->getMessage())
                ->present();
        }
    }
}
