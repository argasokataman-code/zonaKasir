<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationCollection;
use App\Models\Tenants\Product;
use App\Models\Tenants\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return $this->buildResponse()
            ->setData(NotificationCollection::collection(
                auth()->user()
                    ->unreadNotifications()
                    ->where('type', '!=', 'Filament\Notifications\DatabaseNotification')
                    ->get()
            ))
            ->setMessage('success get notification')
            ->present();
    }

    public function update($notification, Product $product): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $notification = User::find(auth()->id())
                ->notifications()
                ->where('id', $notification)
                ->first();
            
            if (!$notification) {
                DB::rollBack();
                return $this->buildResponse()
                    ->setCode(404)
                    ->setMessage('Notification not found')
                    ->present();
            }
            
            $data = array_values(Arr::where($notification->data, function ($data) use ($product) {
                return $data['id'] != $product->id;
            }));
            $notification->data = $data;
            $notification->save();

            if (count($notification->data) == 0) {
                $notification->delete();
            }
            
            DB::commit();

            return $this->buildResponse()
                ->setMessage('Success delete the notification')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to update notification: ' . $e->getMessage())
                ->present();
        }
    }

    public function clear(): JsonResponse
    {
        try {
            DB::beginTransaction();
            auth()->user()->notifications()->delete();
            DB::commit();

            return $this->buildResponse()
                ->setMessage('Success cleared the notification')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to clear notifications: ' . $e->getMessage())
                ->present();
        }
    }
}
