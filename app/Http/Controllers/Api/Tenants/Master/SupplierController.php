<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Supplier;
use App\Services\ApiResponseService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Lakasir\HasCrudAction\Abstracts\HasCrudActionAbstract;
use Lakasir\HasCrudAction\Interfaces\WithSimplePagination;
use Lakasir\HasCrudAction\Resolvers\DestroyActionResolver;
use Lakasir\HasCrudAction\Resolvers\IndexActionResolver;
use Lakasir\HasCrudAction\Resolvers\StoreActionResolver;
use Spatie\QueryBuilder\QueryBuilder;

class SupplierController extends HasCrudActionAbstract implements WithSimplePagination
{
    public static string $model = Supplier::class;

    public function index(IndexActionResolver $resolver): JsonResponse
    {
        $perPage = request()->query('per_page', 15);
        $suppliers = QueryBuilder::for(Supplier::class)
            ->allowedFilters($this->filter())
            ->simplePaginate($perPage);

        return $this->buildResponse()
            ->setData($suppliers)
            ->present();
    }

    public function store(StoreActionResolver $resolver, Request $request): JsonResponse
    {
        Validator::make($request->all(), static::rules(null))->validate();
        $validated = $request->only(array_keys(static::rules(null)));

        try {
            DB::beginTransaction();

            $supplier = Supplier::create($validated);

            DB::commit();

            return $this->buildResponse()
                ->setCode(201)
                ->setData($supplier)
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create supplier: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to create supplier')
                ->present();
        }
    }

    public function destroy(DestroyActionResolver $resolver, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            DB::commit();

            return $this->buildResponse()
                ->setMessage('Supplier deleted successfully')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete supplier: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to delete supplier')
                ->present();
        }
    }

    public function filter(): array
    {
        return [
            'email',
            'name',
            'phone_number',
            'address',
            'city',
            'country',
        ];
    }

    public static function rules($id): array
    {
        return [
            'phone_number' => "unique:suppliers,phone_number,{$id}",
            'email' => "unique:suppliers,email,{$id}",
            'name' => 'required',
        ];
    }

    public function response($record): JsonResponse
    {
        return $this->buildResponse()
            ->setData($record)
            ->present();
    }

    public function buildResponse(): ApiResponseService
    {
        return (new Controller())->buildResponse();
    }
}
