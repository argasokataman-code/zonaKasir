<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterFCMTokenController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|min:10',
        ]);

        $user = User::find(auth()->id());
        $user->fill($request->only('fcm_token'));
        $user->save();

        return $this->buildResponse()
            ->setMessage('FCM token registered successfully')
            ->present();
    }
}
