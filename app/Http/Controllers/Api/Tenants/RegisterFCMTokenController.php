<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegisterFCMTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|min:10',
        ]);

        try {
            DB::beginTransaction();

            $user = User::find(auth()->id());
            $user->fill($request->only('fcm_token'));
            $user->save();

            DB::commit();

            return $this->buildResponse()
                ->setMessage('FCM token registered successfully')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to register FCM token: ' . $e->getMessage())
                ->present();
        }
    }
}
