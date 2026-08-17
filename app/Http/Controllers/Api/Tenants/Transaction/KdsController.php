<?php

namespace App\Http\Controllers\Api\Tenants\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Tenants\SellingDetail;
use App\Services\Tenants\KdsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KdsController extends Controller
{
    public function __construct(private KdsService $kds)
    {
    }

    public function orders(): JsonResponse
    {
        return $this->buildResponse()
            ->setData($this->kds->orders())
            ->setMessage('success get kitchen orders')
            ->present();
    }

    public function updateStatus(Request $request, SellingDetail $sellingDetail): JsonResponse
    {
        $this->kds->updateStatus($sellingDetail, $request->input('status'));

        return $this->buildResponse()
            ->setMessage('success update kitchen status')
            ->present();
    }
}
