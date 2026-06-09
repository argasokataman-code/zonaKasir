<?php

namespace App\Http\Controllers\Api\Tenants\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenants\SecureInitialPrice;
use App\Models\Tenants\Setting;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecureInitialPriceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (!Setting::get('secure_initial_price_enabled', false)) {
            return $this->buildResponse()
                ->setMessage('secure initial price is not enabled')
                ->setCode(403)
                ->present();
        }

        $this->validate($request, [
            'old_password' => ['nullable', 'string'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        if ($request->filled('old_password')) {
            if (!auth()->user()->secureInitialPrice) {
                return $this->buildResponse()
                    ->setMessage('secure initial price password has not been set')
                    ->setCode(403)
                    ->present();
            }

            if (!Hash::check($request->old_password, auth()->user()->secureInitialPrice->password)) {
                return $this->buildResponse()
                    ->setMessage('old password is not valid')
                    ->setCode(403)
                    ->present();
            }

            try {
                DB::beginTransaction();

                auth()->user()->secureInitialPrice->update([
                    'password' => bcrypt($request->password),
                ]);

                DB::commit();

                return $this->buildResponse()
                    ->setMessage('secure initial price password has been updated')
                    ->present();
            } catch (Exception $e) {
                DB::rollBack();

                return $this->buildResponse()
                    ->setCode(500)
                    ->setMessage('Failed to update password: ' . $e->getMessage())
                    ->present();
            }
        }

        if (auth()->user()->secureInitialPrice) {
            return $this->buildResponse()
                ->setMessage('secure initial price password has already been set')
                ->setCode(403)
                ->present();
        }

        try {
            DB::beginTransaction();

            auth()->user()->secureInitialPrice()->create([
                'password' => bcrypt($request->password),
            ]);

            DB::commit();

            return $this->buildResponse()
                ->setMessage('secure initial price password has been set')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to set password: ' . $e->getMessage())
                ->present();
        }
    }

    public function verify(Request $request): JsonResponse
    {
       if (!Setting::get('secure_initial_price_enabled', false)) {
            return $this->buildResponse()
                ->setMessage('secure initial price is not enabled')
                ->setCode(403)
                ->present();
        }

        $this->validate($request, [
            'password' => ['required', 'string'],
        ]);

        $user_id = $request->user_id ?? auth()->user()->id;

        $secureInitialPrice = SecureInitialPrice::where('user_id', $user_id)->first();

        if ($secureInitialPrice) {
            $checkMethod = Hash::check($request->password, $secureInitialPrice->password);
        }

        $checkMethod = Hash::check($request->password, auth()->user()->password);

        if (!$checkMethod) {
            return $this->buildResponse()
                ->setMessage('secure initial price password is not valid')
                ->setCode(403)
                ->present();
        }

        return $this->buildResponse()
            ->setMessage('secure initial price password is valid')
            ->present();
    }
}
