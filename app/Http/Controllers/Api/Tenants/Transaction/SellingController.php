<?php

namespace App\Http\Controllers\Api\Tenants\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Sellings\TransactionSellingStoreRequest;
use App\Http\Resources\SellingCollection;
use App\Models\Tenants\Selling;
use App\Services\Tenants\MidtransGatewayService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

class SellingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sellings = QueryBuilder::for(Selling::class)
            ->select('id', 'code', 'date', 'total_price', 'grand_total_price', 'total_qty', 'total_cost', 'discount_price', 'total_discount_per_item', 'tax_price', 'payed_money', 'money_changes', 'is_paid', 'fee', 'member_id', 'payment_method_id', 'user_id', 'created_at')
            ->allowedFilters([
                'code',
                'member_id',
                'date',
                'code',
                'payed_money',
                'money_changes',
                'total_price',
                'total_qty',
                'created_at',
                'updated_at',
                'sellingDetails.product_id',
            ])
            ->with([
                'member:id,name',
                'paymentMethod:id,name,is_credit,payment_type',
                'sellingDetails:id,selling_id,product_id,qty,price,cost,discount_price',
                'sellingDetails.product:id,name,sku',
                'user:id,name',
            ])
            ->isPaid()
            ->defaultSort('-created_at')
            ->simplePaginate($request->get('per_page', 10));

        return $this->buildResponse()
            ->setData(SellingCollection::collection($sellings))
            ->setMessage('success get sellings')
            ->present();
    }

    public function store(TransactionSellingStoreRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $selling = $request->store();
            $selling->load([
                'member:id,name',
                'paymentMethod:id,name,is_credit,payment_type',
                'sellingDetails:id,selling_id,product_id,qty,price,cost,discount_price',
                'sellingDetails.product:id,name,sku',
                'user:id,name',
            ]);

            // Handle Midtrans payment (digital payment methods)
            $response = new SellingCollection($selling);

            if ($selling->paymentMethod && $selling->paymentMethod->isMidtrans()) {
                $midtransType = $selling->paymentMethod->midtransType();
                abort_if(
                    ! $midtransType,
                    422,
                    'Payment method type is not configured for Midtrans. Please set payment_type.'
                );

                $midtransGateway = app(MidtransGatewayService::class);
                $snapData = $midtransGateway->createSnapToken($selling, $midtransType);
                $response->additional['snap_token'] = $snapData['token'];
                $response->additional['snap_redirect_url'] = $snapData['redirect_url'];
                $response->additional['midtrans_payment_id'] = $snapData['midtrans_payment_id'];
                $response->additional['is_midtrans_payment'] = true;
            }

            DB::commit();

            return $this->buildResponse()
                ->setCode(201)
                ->setMessage('success create selling')
                ->setData($response)
                ->present();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to create selling: ' . $e->getMessage())
                ->present();
        }
    }

    public function show(Selling $selling): JsonResponse
    {
        $selling->load([
            'member:id,name',
            'paymentMethod:id,name,is_credit,payment_type',
            'sellingDetails:id,selling_id,product_id,qty,price,cost,discount_price',
            'sellingDetails.product:id,name,sku',
            'user:id,name',
        ]);

        return $this->buildResponse()
            ->setData(new SellingCollection($selling))
            ->setMessage('success get selling')
            ->present();
    }
}
