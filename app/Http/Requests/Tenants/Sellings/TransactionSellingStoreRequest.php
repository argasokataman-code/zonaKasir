<?php

namespace App\Http\Requests\Tenants\Sellings;

use App\Models\Tenants\CashDrawer;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Selling;
use App\Models\Tenants\Setting;
use App\Rules\CheckProductStock;
use App\Rules\ShouldSameWithSellingDetail;
use App\Services\Tenants\SellingService;
use App\Services\VoucherService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionSellingStoreRequest extends FormRequest
{
    public function __construct(
        private SellingService $sellingService,
        private VoucherService $voucherService
    ) {

    }

    public function authorize(): bool
    {
        if (Setting::get('cash_drawer_enabled', false)) {
            $lastOpenedCashDrawer = CashDrawer::lastOpened()->select('id')->first();
            if (! $lastOpenedCashDrawer) {
                throw ValidationException::withMessages([
                    'cash_drawer' => 'Cash drawer is not opened',
                ]);
            }
        }

        return true;
    }

    protected function prepareForValidation()
    {
        $data = $this->all();

        // Map selling_details to products if products not provided
        if (! isset($data['products']) && isset($data['selling_details'])) {
            $data['products'] = array_map(function ($item) {
                return [
                    'product_id' => $item['product_id'] ?? null,
                    'qty' => $item['quantity'] ?? $item['qty'] ?? 1,
                    'price' => $item['price'] ?? null,
                    'discount_price' => $item['discount_price'] ?? 0,
                    'price_unit_id' => $item['price_unit_id'] ?? null,
                ];
            }, $data['selling_details']);
        }

        // Default friend_price to false if not provided
        if (! array_key_exists('friend_price', $data)) {
            $data['friend_price'] = false;
        }

        // Default payed_money to total_price if not provided
        if (! isset($data['payed_money']) && isset($data['total_price'])) {
            $data['payed_money'] = $data['total_price'];
        }

        $this->replace($data);
        $this->merge($this->sellingService->mapProductRequest($this->all()));
    }

    public function rules(): array
    {
        $request = $this->all();
        $pMethod = PaymentMethod::select('id', 'is_credit')->find($request['payment_method_id'] ?? null);
        $totalPrice = ($request['total_price'] ?? 0) - ($request['discount_price'] ?? 0) - ($request['total_discount_per_item'] ?? 0);

        return [
            'fee' => ['numeric'],
            'payed_money' => [
                'required',
                ($pMethod && ! $pMethod->is_credit) ? 'gte:'.$totalPrice : null,
                $pMethod ? Rule::requiredIf(fn () => ! $pMethod->is_credit) : 'required',
            ],
            'total_price' => ['required_if:friend_price,true', 'numeric'],
            'total_qty' => ['required_if:friend_price,true', 'numeric', new ShouldSameWithSellingDetail('qty', $this->products ?? [])],
            'friend_price' => ['nullable', 'boolean'],
            'voucher' => [function ($attribute, $value, $fail) {
                if (! $this->voucherService->applyable($value, $this->total_price)) {
                    $fail(__('voucher expired'));
                }
            }],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['required', 'exists:products,id'],
            'products.*.price' => ['nullable', 'required_if:friend_price,true', 'numeric'],
            'products.*.discount_price' => ['nullable', 'required_if:friend_price,true', 'numeric'],
            'products.*.qty' => ['required', 'numeric', 'min:1', new CheckProductStock],
        ];
    }

    public function store(): Selling
    {
        return $this->sellingService->create($this->all());
    }
}
