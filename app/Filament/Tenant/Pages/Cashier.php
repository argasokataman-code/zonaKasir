<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Pages\Traits\CartForm;
use App\Filament\Tenant\Pages\Traits\CartInteraction;
use App\Filament\Tenant\Pages\Traits\MemberHandler;
use App\Filament\Tenant\Pages\Traits\PaymentHandler;
use App\Filament\Tenant\Pages\Traits\PriceCalculation;
use App\Filament\Tenant\Pages\Traits\TableProduct;
use App\Filament\Tenant\Pages\Traits\VoucherHandler;
use App\Filament\Tenant\Resources\Traits\RefreshThePage;
use App\Models\Tenants\About;
use App\Models\Tenants\CartItem;
use App\Models\Tenants\Member;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Profile;
use App\Models\Tenants\Setting;
use App\Models\Tenants\Table;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as CollectionSupport;

class Cashier extends Page implements HasForms, HasTable
{
    use CartInteraction, HasTranslatableResource, RefreshThePage, TableProduct;
    use PriceCalculation, VoucherHandler, MemberHandler, PaymentHandler, CartForm;

    public static ?string $label = 'POS';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static string $view = 'filament.tenant.pages.cashier';

    protected static string $layout = 'filament-panels::components.layout.base';

    public Collection $cartItems;

    public Collection $availableVoucher;

    public array $cartDetail = [];

    public array $paymentMethods;

    public CollectionSupport $members;

    public float $tax;

    public string $currency;

    public string $locale;

    public float $sub_total = 0;

    public float $total_price = 0;

    public ?About $about;

    public ?Collection $tableOption;

    private float $discount_price = 0;

    public static function canAccess(): bool
    {
        return can('create selling');
    }

    public function mount(): void
    {
        $this->about = About::first() ?? null;

        $this->tax = (float) Setting::get('default_tax', 0);

        $this->currency = Setting::get('currency', 'IDR');

        $this->locale = Profile::get()->locale ?? 'en';

        $this->cartItems = CartItem::query()
            ->select('*')
            ->with('product')
            ->orderByDesc('created_at')
            ->cashier()
            ->get();

        $this->loadAvailableVouchers();

        $this->calculateTotalPrice();

        $this->paymentMethods = PaymentMethod::query()
            ->select('id', 'name', 'is_credit', 'payment_type')
            ->get()
            ->toArray();

        $this->members = Member::query()
            ->select('id', 'name')
            ->get()
            ->pluck('name', 'id');

        $this->tableOption = Table::select('id', 'number')->get();

        $this->storeCartForm->fill(array_merge($this->cartDetail, [
            'payment_method_id' => 1,
            'total_price' => $this->total_price,
            'friend_price' => false,
        ]));

        $this->fillPaymentMethodLabel();
    }

    public function storeCart(): void
    {
        if ($this->cartDetail['voucher']) {
            $this->validateVoucher($this->cartDetail['voucher']);
        }

        if ($discount_price = str_replace(',', '', $this->cartDetail['discount_price'])) {
            $this->cartItems->each(function (CartItem $item) {
                if ($item->discount_price && $item->discount_price > 0) {
                    $this->discount_price += $item->discount_price;
                }
            });
            $this->total_price = $this->calculateTotalPriceWithManualDiscount(floatval($discount_price));
        }

        $this->fillMember();
        $this->fillPaymentMethodLabel();

        $this->dispatch('close-modal', id: 'edit-detail');
    }
}