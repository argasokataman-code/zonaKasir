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

        // Must select a payment method first
        if (empty($this->cartDetail['payment_method_id'])) {
            $this->dispatch('payment-method-missing');
            return;
        }

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
            Notification::make()
                ->title(__('Payment method not found'))
                ->body(__('Please select a valid payment method.'))
                ->warning()
                ->send();

            return;
        }

        if ($pMethod->isMidtrans()) {
            $this->handleMidtransPayment($request, $pMethod, $sellingService);
            return;
        }

        $this->handleCashPayment($request, $pMethod, $sellingService);
    }

    private function handleMidtransPayment(array $request, PaymentMethod $pMethod, SellingService $sellingService): void
    {
        $midtransType = $pMethod->midtransType();
        if (! $midtransType) {
            return;
        }

        $request['payed_money'] = $this->total_price;
        $request['money_changes'] = 0;

        $validator = Validator::make($request, [
            'payment_method_id' => ['required'],
            'total_price' => ['required_if:friend_price,true', 'numeric'],
            'friend_price' => ['required', 'boolean'],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.qty' => ['required', 'numeric', 'min:1', new CheckProductStock],
        ]);
        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->messages()->toArray());
        }

        $data = array_merge($request, $sellingService->mapProductRequest($request));

        $gateway = app(\App\Services\Tenants\MidtransGatewayService::class);
        $snapData = $gateway->createPaymentIntent($data, $midtransType);

        $this->dispatch('close-modal', id: 'proceed-the-payment');
        $this->dispatch('midtrans-payment', [
            'order_id' => $snapData['order_id'],
            'token' => $snapData['token'],
            'redirect_url' => $snapData['redirect_url'],
            'payment_type' => $midtransType,
            'amount' => $this->total_price,
        ]);
    }

    public function confirmMidtransPayment(string $orderId, SellingService $sellingService): void
    {
        $payment = \App\Models\Tenants\MidtransPayment::where('order_id', $orderId)
            ->where('status', 'pending')
            ->first();

        if (! $payment) {
            Log::warning('confirmMidtransPayment: payment not found', ['order_id' => $orderId]);
            return;
        }

        if ($payment->selling_id) {
            return;
        }

        $cartData = $payment->cart_data;
        if (! $cartData) {
            Log::error('confirmMidtransPayment: no cart_data', ['order_id' => $orderId]);
            return;
        }

        $data = array_merge($cartData, $sellingService->mapProductRequest($cartData));
        $selling = $sellingService->create($data);

        $payment->update([
            'selling_id' => $selling->id,
            'status' => 'settlement',
            'paid_at' => now(),
        ]);

        app(\App\Services\Tenants\MidtransGatewayService::class)->confirmSettlement($payment);

        CartItem::query()->cashier()->delete();

        Notification::make()
            ->title(__('Transaction created'))
            ->success()
            ->send();

        $this->mount();
    }

    private function handleCashPayment(array $request, PaymentMethod $pMethod, SellingService $sellingService): void
    {
        $validator = Validator::make($request, [
            'fee' => ['numeric'],
            'payment_method_id' => ['required'],
            'member_id' => Rule::requiredIf(fn () => $pMethod->is_credit),
            'due_date' => Rule::requiredIf(fn () => $pMethod->is_credit),
            'payed_money' => [
                ! $pMethod->is_credit ? 'gte:total_price' : null,
                Rule::requiredIf(fn () => ! $pMethod->is_credit),
                ! $pMethod->is_credit ? 'lte:999999999' : null,
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

        CartItem::query()
            ->cashier()
            ->delete();

        Notification::make()
            ->title(__('Transaction created'))
            ->success()
            ->send();

        $this->mount();

        $this->dispatch('selling-created', selling: $selling->load('sellingDetails.product', 'table', 'paymentMethod'));
    }
}
