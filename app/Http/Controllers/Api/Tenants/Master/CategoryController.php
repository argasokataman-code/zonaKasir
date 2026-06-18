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
            ->select('id', 'name', 'created_at')
            ->allowedFilters(['name'])
            ->orderByDesc('created_at')
            ->simplePaginate($this->resolvePerPage(request()) ?? 15);

        return $this->buildResponse()
            ->setData(new CategoryCollection($categories))
            ->present();
    }

    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|unique:categories,name',
        ]);
        
        try {
            $category = new Category();
            $category->fill($request->only('name'));
            $category->save();
            
            return $this->buildResponse()
                ->setData(new CategoryCollection($category))
                ->setCode(201)
                ->setMessage('Category created successfully')
                ->present();
        } catch (Exception $e) {
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
            'name' => "required|unique:categories,name,{$category->id}",
        ]);
        
        try {
            
            $category->fill($request->only('name'));
            $category->save();
            
            
            return $this->buildResponse()
                ->setData(new CategoryCollection($category))
                ->setMessage('Category updated successfully')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to update category: ' . $e->getMessage())
                ->present();
        }
    }

    public function destroy(Category $category): JsonResponse
    {
        if ($category->products()->exists()) {
            return $this->buildResponse()
                ->setCode(400)
                ->setMessage('Category has products and cannot be deleted')
                ->present();
        }
        
        try {
            $category->delete();

            return $this->buildResponse()
                ->setMessage('Category deleted successfully')
                ->present();
        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to delete category: ' . $e->getMessage())
                ->present();
        }
    }
}
