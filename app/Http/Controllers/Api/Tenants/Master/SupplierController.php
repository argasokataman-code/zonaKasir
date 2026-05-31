<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Supplier;
use Illuminate\Http\Request;
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

    public function index(IndexActionResolver $resolver)
    {
        $perPage = request()->query('per_page', 15);
        $suppliers = QueryBuilder::for(Supplier::class)
            ->allowedFilters($this->filter())
            ->simplePaginate($perPage);

        return $this->buildResponse()
            ->setData($suppliers)
            ->present();
    }

    public function store(StoreActionResolver $resolver, Request $request)
    {
        Validator::make($request->all(), static::rules(null))->validate();

        $supplier = Supplier::create($request->all());

        return $this->buildResponse()
            ->setCode(201)
            ->setData($supplier)
            ->present();
    }

    public function destroy(DestroyActionResolver $resolver, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return response()->noContent();
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

    public function response($record)
    {
        return $this->buildResponse()
            ->setData($record)
            ->present();
    }

    public function buildResponse()
    {
        return (new Controller())->buildResponse();
    }
}
