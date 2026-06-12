<?php

namespace App\Http\Controllers\Api\Tenants\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Resources\WithdrawalCollection;
use App\Models\Tenants\Withdrawal;
use App\Services\Tenants\WithdrawalService;
use App\Services\Tenants\InsufficientBalanceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $withdrawals = Withdrawal::query()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->buildResponse()
            ->setData(WithdrawalCollection::collection($withdrawals))
            ->setMessage('success get withdrawals')
            ->present();
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount'           => 'required|numeric|min:50000',
            'idempotency_key'  => 'required|string|max:100',
        ]);

        try {
            $withdrawal = app(WithdrawalService::class)->request(
                amount: $validated['amount'],
                idempotencyKey: $validated['idempotency_key'],
            );

            return $this->buildResponse()
                ->setCode(201)
                ->setMessage('success request withdrawal')
                ->setData(new WithdrawalCollection($withdrawal))
                ->present();

        } catch (InsufficientBalanceException $e) {
            throw ValidationException::withMessages([
                'amount' => $e->getMessage(),
            ]);
        }
    }

    public function approve(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        abort_if($withdrawal->status !== 'pending', 400, 'Withdrawal already processed');

        $withdrawal = app(WithdrawalService::class)->approve($withdrawal->id, auth()->id());

        return $this->buildResponse()
            ->setMessage('Withdrawal approved')
            ->setData(new WithdrawalCollection($withdrawal))
            ->present();
    }

    public function reject(Request $request, Withdrawal $withdrawal): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        abort_if($withdrawal->status !== 'pending', 400, 'Withdrawal already processed');

        $withdrawal = app(WithdrawalService::class)->reject($withdrawal->id, auth()->id(), $validated['reason']);

        return $this->buildResponse()
            ->setMessage('Withdrawal rejected')
            ->setData(new WithdrawalCollection($withdrawal))
            ->present();
    }
}
