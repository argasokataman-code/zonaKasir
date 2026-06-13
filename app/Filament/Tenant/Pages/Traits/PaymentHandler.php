<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Models\Tenants\CartItem;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Selling;
use App\Rules\CheckProductStock;
use App\Rules\ShouldSameWithSellingDetail;
use App\Services\Tenants\SellingService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait PaymentHandler
{
    public function setPaymentMethodId(int $paymentMethodId): void
    {
        $this->cartDetail['payment_method_id'] = $paymentMethodId;
    }

    protected function fillPaymentMethodLabel(): void
    {
        $paymentMethod = collect($this->paymentMethods)->filter(function ($value, int $key) {
            return $value['id'] == $this->cartDetail['payment_method_id'];
        })->first();
        if (isset($paymentMethod['name'])) {
            $this->cartDetail['payment_method_label'] = $paymentMethod['name'];
        }
    }

    public function proceedThePayment(SellingService $sellingService): void
    {
        $this->cartDetail = array_merge($this->cartDetail, [
            'total_price' => $this->total_price,
        ]);

        $request = array_merge($this->cartDetail, [
            'discount_price' => floatval(str_replace(',', '', $this->cartDetail['discount_price'])),
            'products' => $this->cartItems->map(function (CartItem $cartItem) {
                return [
                    'product_id' => $cartItem->product_id,
                    'qty' => $cartItem->qty,
                    'price' => $cartItem->price,
                    'discount_price' => $cartItem->discount_price,
                    'price_unit_id' => $cartItem->price_unit_id,
                ];
            })->toArray(),
        ]);

        $pMethod = PaymentMethod::find($request['payment_method_id']);

        Log::info('PaymentMethod check', [
            'payment_method_id' => $request['payment_method_id'] ?? null,
            'pMethod_name' => $pMethod->name ?? null,
            'pMethod_payment_type' => $pMethod->payment_type ?? null,
            'isMidtrans' => $pMethod?->isMidtrans(),
        ]);

        if (! $pMethod) {
            $pMethod = PaymentMethod::create([
                'name' => 'Cash',
                'is_cash' => true,
                'is_debit' => false,
                'is_credit' => false,
                'is_wallet' => false,
                'icon' => 'assets/images/payment-methods/cash.png',
            ]);
        }

        if ($pMethod->isMidtrans()) {
            $request['payed_money'] = $this->total_price;
            $request['money_changes'] = 0;
        }

        $validator = Validator::make($request, [
            'fee' => ['numeric'],
            'payment_method_id' => ['required'],
            'member_id' => Rule::requiredIf(fn () => $pMethod->is_credit),
            'due_date' => Rule::requiredIf(fn () => $pMethod->is_credit),
            'payed_money' => [
                ! $pMethod->is_credit ? 'gte:total_price' : null,
                Rule::requiredIf(fn () => ! $pMethod->is_credit),
            ],
            'total_price' => ['required_if:friend_price,true', 'numeric'],
            'total_qty' => ['required_if:friend_price,true', 'numeric', new ShouldSameWithSellingDetail('qty', $request['products'])],
            'friend_price' => ['required', 'boolean'],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.price' => ['required_if:friend_price,true', 'numeric'],
            'products.*.qty' => ['required', 'numeric', 'min:1', new CheckProductStock],
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());

            return;
        }
        $data = array_merge($request, $sellingService->mapProductRequest($request));
        $selling = $sellingService->create($data);

        if ($pMethod->isMidtrans()) {
            $midtransType = $pMethod->midtransType();
            if ($midtransType) {
                $selling->load(['member', 'paymentMethod', 'sellingDetails.product', 'user']);
                $snapData = app(\App\Services\Tenants\MidtransGatewayService::class)
                    ->createSnapToken($selling, $midtransType);

                CartItem::query()->cashier()->delete();

                $this->dispatch('close-modal', id: 'proceed-the-payment');
                $this->dispatch('midtrans-payment', [
                    'token' => $snapData['token'],
                    'redirect_url' => $snapData['redirect_url'],
                    'payment_type' => $midtransType,
                    'amount' => $selling->total_price,
                ]);
                return;
            }
        }

        CartItem::query()
            ->cashier()
            ->delete();

        Notification::make()
            ->title(__('Transaction created'))
            ->success()
            ->send();

        $this->mount();

        $this->dispatch('selling-created', selling: $selling->load('sellingDetails.product', 'table'));
    }
}