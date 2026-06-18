<?php

namespace App\Http\Controllers\Api\Tenants\Transaction;

use App\Http\Controllers\Controller;
use App\Models\Tenants\CashDrawer;
use App\Models\Tenants\User;
use App\Notifications\CashDrawerAlert;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashDrawerController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'opening_balance' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        try {
            DB::beginTransaction();

            $lastOpenedCashDrawer = CashDrawer::select('id', 'cash', 'opened_by')->lastOpened()->first();
            if ($lastOpenedCashDrawer) {
                $lastOpenedCashDrawer->update([
                    'cash' => $request->opening_balance,
                ]);
            } else {
                $lastOpenedCashDrawer = CashDrawer::create([
                    'cash' => $request->opening_balance,
                    'opened_by' => auth()->id(),
                ]);
            }

            DB::commit();

            if ($lastOpenedCashDrawer) {
                User::select('id')->each(function ($user) use ($lastOpenedCashDrawer) {
                    $user->notify(new \App\Notifications\CashDrawerAlert($lastOpenedCashDrawer, 'opened'));
                });
            }

            return $this->buildResponse()
                ->setData($lastOpenedCashDrawer)
                ->setMessage('Cash drawer opened successfully')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to store cash drawer: ' . $e->getMessage())
                ->present();
        }
    }

    public function close(): JsonResponse
    {
        try {
            DB::beginTransaction();
            
            $lastOpenedCashDrawer = CashDrawer::select('id', 'cash', 'opened_by', 'closed_by')->lastOpened()->first();
            if (!$lastOpenedCashDrawer) {
                DB::rollBack();
                return $this->buildResponse()
                    ->setMessage('Cash drawer already closed or not opened yet')
                    ->setCode(422)
                    ->present();
            }

            $lastOpenedCashDrawer->update([
                'closed_by' => auth()->id()
            ]);

            DB::commit();

            if ($lastOpenedCashDrawer) {
                User::select('id')->each(function ($user) use ($lastOpenedCashDrawer) {
                    $user->notify(new \App\Notifications\CashDrawerAlert($lastOpenedCashDrawer, 'closed'));
                });
            }

            return $this->buildResponse()
                ->setData($lastOpenedCashDrawer)
                ->setMessage('Cash drawer closed successfully')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to close cash drawer: ' . $e->getMessage())
                ->present();
        }
    }

    public function show(): JsonResponse
    {
        $lastOpenedCashDrawer = CashDrawer::select('id', 'cash', 'opened_by', 'closed_by', 'created_at')->lastOpened()->first();

        return $this->buildResponse()
            ->setData($lastOpenedCashDrawer)
            ->present();
    }
}
