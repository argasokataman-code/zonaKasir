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
                    'opening_amount' => $request->opening_balance,
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

    public function close(Request $request): JsonResponse
    {
        $request->validate([
            'closing_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            $lastOpenedCashDrawer = CashDrawer::select('*')->lastOpened()->first();
            if (!$lastOpenedCashDrawer) {
                DB::rollBack();
                return $this->buildResponse()
                    ->setMessage('Cash drawer already closed or not opened yet')
                    ->setCode(422)
                    ->present();
            }

            // FR-6.3: Z-report — expected = Σ penjualan cash selama shift
            $expectedCash = \App\Models\Tenants\SellingPayment::query()
                ->where('status', 'success')
                ->where('is_cash', true)
                ->where('payment_date', '>=', $lastOpenedCashDrawer->created_at)
                ->whereHas('selling', fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->sum('amount');

            $opening = (float) $lastOpenedCashDrawer->opening_amount ?? (float) $lastOpenedCashDrawer->cash;
            $actual = (float) $request->closing_amount - $opening;

            $lastOpenedCashDrawer->update([
                'closed_by' => auth()->id(),
                'closed_at' => now(),
                'closing_amount' => $request->closing_amount,
                'expected_total' => $expectedCash,
                'actual_total' => $actual,
                'difference' => $actual - $expectedCash,
            ]);

            DB::commit();

            if ($lastOpenedCashDrawer) {
                User::select('id')->each(function ($user) use ($lastOpenedCashDrawer) {
                    $user->notify(new \App\Notifications\CashDrawerAlert($lastOpenedCashDrawer, 'closed'));
                });
            }

            return $this->buildResponse()
                ->setData($lastOpenedCashDrawer)
                ->setMessage('Shift closed successfully')
                ->present();
        } catch (Exception $e) {
            DB::rollBack();
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Failed to close cash drawer: ' . $e->getMessage())
                ->present();
        }
    }

    /**
     * FR-6.4: breakdown penjualan per metode bayar per kasir utk drawer ini.
     * Sumber kebenaran = selling_payments rows (multi-payment/split aware).
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'shift_id' => ['nullable', 'integer', 'exists:cash_drawers,id'],
        ]);

        $drawerId = $request->shift_id;
        if (! $drawerId) {
            $drawer = CashDrawer::lastOpened()->first();
            $drawerId = $drawer?->id;
        }
        if (! $drawerId) {
            return $this->buildResponse()
                ->setMessage('No shift found')
                ->setCode(422)
                ->present();
        }

        $drawer = CashDrawer::with('openedBy', 'closedBy')->findOrFail($drawerId);

        $payments = \App\Models\Tenants\SellingPayment::query()
            ->where('selling_payments.status', 'success')
            ->whereHas('selling', fn ($q) => $q->where('cash_drawer_id', $drawerId)->where('sellings.status', '!=', 'cancelled'))
            ->selectRaw('payment_method_id, COUNT(*) as tx_count, SUM(amount) as total')
            ->groupBy('payment_method_id')
            ->with('paymentMethod:id,name')
            ->get();

        $byMethod = $payments->map(fn ($p) => [
            'payment_method' => $p->paymentMethod?->name ?? 'Unassigned',
            'tx_count' => (int) $p->tx_count,
            'total' => (float) $p->total,
        ]);
        $byCashier = \App\Models\Tenants\SellingPayment::query()
            ->where('selling_payments.status', 'success')
            ->whereHas('selling', fn ($q) => $q->where('cash_drawer_id', $drawerId)->where('sellings.status', '!=', 'cancelled'))
            ->join('sellings', 'sellings.id', '=', 'selling_payments.selling_id')
            ->selectRaw('sellings.user_id, COUNT(*) as tx_count, SUM(selling_payments.amount) as total')
            ->groupBy('sellings.user_id')
            ->with(['selling.user:id,name'])
            ->get()
            ->map(fn ($p) => [
                'cashier' => $p->selling->user->name ?? 'Unknown',
                'tx_count' => (int) $p->tx_count,
                'total' => (float) $p->total,
            ]);

        return $this->buildResponse()
            ->setData([
                'shift_no' => $drawer->shift_no,
                'opened_at' => $drawer->created_at,
                'closed_at' => $drawer->closed_at,
                'opening_amount' => $drawer->opening_amount,
                'closing_amount' => $drawer->closing_amount,
                'expected_total' => $drawer->expected_total,
                'actual_total' => $drawer->actual_total,
                'difference' => $drawer->difference,
                'by_method' => $byMethod,
                'by_cashier' => $byCashier,
            ])
            ->present();
    }

    public function show(): JsonResponse
    {
        $lastOpenedCashDrawer = CashDrawer::select('id', 'cash', 'opened_by', 'closed_by', 'created_at', 'shift_no', 'opening_amount')->lastOpened()->first();

        return $this->buildResponse()
            ->setData($lastOpenedCashDrawer)
            ->present();
    }
}
