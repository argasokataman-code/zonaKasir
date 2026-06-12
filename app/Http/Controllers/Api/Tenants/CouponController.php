<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Services\CouponService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function redeem(Request $request, CouponService $couponService): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        try {
            $tenantId = tenant('id');
            if (! $tenantId) {
                return $this->buildResponse()
                    ->setCode(400)
                    ->setMessage('Tenant context not found')
                    ->present();
            }

            $result = $couponService->redeem($request->code, $tenantId);

            return $this->buildResponse()
                ->setData($result)
                ->setMessage($result['message'] ?? 'Kupon berhasil digunakan')
                ->present();

        } catch (Exception $e) {
            return $this->buildResponse()
                ->setCode(400)
                ->setMessage($e->getMessage())
                ->present();
        }
    }
}