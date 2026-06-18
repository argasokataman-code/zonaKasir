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
use App\Models\Tenants\Category;
use App\Models\Tenants\Member;
use App\Models\Tenants\PaymentMethod;
use App\Models\Tenants\Product;
use App\Models\Tenants\Profile;
use App\Models\Tenants\Setting;
use App\Models\Tenants\Table;
use App\Traits\HasTranslatableResource;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as CollectionSupport;

class Cashier extends Page implements HasForms
{
    use CartInteraction, HasTranslatableResource, RefreshThePage, TableProduct;
    use PriceCalculation, VoucherHandler, MemberHandler, PaymentHandler, CartForm;

    public static ?string $label = 'POS';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static string $view = 'filament.tenant.pages.cashier';

    protected static string $layout = 'filament-panels::components.layout.base';

    public Collection $cartItems;

    public int $cartCount = 0;

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

    public ?Collection $products;

    public ?Collection $categories;

    public ?string $search = null;

    public ?int $selectedCategory = null;

    private float $discount_price = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategory' => ['except' => ''],
    ];

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

        $this->cartCount = $this->cartItems->count();

        $this->loadAvailableVouchers();

        $this->calculateTotalPrice();

        $this->paymentMethods = PaymentMethod::query()
            ->select('id', 'name', 'is_credit', 'payment_type')
            ->where('is_active', true)
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

        $this->categories = Category::all();
        $this->loadProducts();
    }

    /**
     * Lightweight refresh after cart actions (add/reduce/delete/update/clear).
     * Only re-queries cart items + recalculates totals — no products/categories/etc.
     * 2 DB queries vs mount()'s 15+.
     */
    protected function refreshCart(): void
    {
        $this->cartItems = CartItem::query()
            ->with('product', 'priceUnit')
            ->orderByDesc('created_at')
            ->cashier()
            ->get();

        $this->cartCount = $this->cartItems->count();

        $this->calculateTotalPrice();

        $this->loadAvailableVouchers();

        $this->storeCartForm->fill(array_merge($this->cartDetail, [
            'payment_method_id' => $this->cartDetail['payment_method_id'] ?? 1,
            'total_price' => $this->total_price,
            'friend_price' => $this->cartDetail['friend_price'] ?? false,
        ]));

        $this->fillPaymentMethodLabel();
    }

    public function loadProducts(): void
    {
        $query = Product::query()
            ->where(function ($query) {
                $query->where('type', 'product')
                    ->where(function ($query) {
                        $query->whereHas('stocks', function ($query) {
                            $query->where('is_ready', 1)
                                ->where('type', 'in')
                                ->where('stock', '>', 0);
                        })
                        ->orWhere('is_non_stock', true);
                    })
                ->orWhere('type', 'service');
            })
            ->where('show', true);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('sku', 'like', "%{$this->search}%")
                  ->orWhere('name', 'like', "%{$this->search}%")
                  ->orWhereHas('barcodes', function ($q) {
                      $q->where('code', 'like', "%{$this->search}%")
                        ->where('is_active', true);
                  });
            });
        }

        if ($this->selectedCategory) {
            $query->where('category_id', $this->selectedCategory);
        }

        $this->products = $query->with(['stocks' => fn ($q) => $q->where('is_ready', 1)->where('type', 'in')])->get();
    }

    public function updatedSearch(): void
    {
        $this->loadProducts();
    }

    public function updatedSelectedCategory(): void
    {
        $this->loadProducts();
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

    public string $newMemberName = '';
    public string $newMemberPhone = '';

    public function quickCreateMember(): void
    {
        $this->validate([
            'newMemberName' => 'required|string|max:255',
        ], [
            'newMemberName.required' => 'Member name is required.',
        ]);

        $member = Member::create([
            'name' => $this->newMemberName,
            'email' => $this->newMemberPhone,
            'identity_number' => $this->newMemberPhone,
            'joined_date' => now(),
        ]);

        $this->members = Member::query()
            ->select('id', 'name')
            ->get()
            ->pluck('name', 'id');

        $this->cartDetail['member_id'] = $member->id;
        $this->cartDetail['member_label'] = $member->name;

        $this->newMemberName = '';
        $this->newMemberPhone = '';

        $this->dispatch('close-modal', id: 'modal-quick-member');
        $this->dispatch('member-created', memberId: $member->id, memberName: $member->name);
    }

    /**
     * Return all data needed for PWA offline IndexedDB sync.
     * Uses Livewire's auth session instead of raw fetch (which lacks tenant context).
     */
    public function getOfflineSyncData(): array
    {
        return [
            'products' => Product::query()
                ->select('id', 'name', 'sku', 'selling_price', 'is_non_stock', 'category_id', 'hero_images')
                ->with('category:id,name', 'primaryBarcode:product_id,code')
                ->orderByDesc('created_at')
                ->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'barcode' => $p->primaryBarcode->first()?->code ?? null,
                    'selling_price' => $p->selling_price,
                    'selling_price_calculate' => $p->selling_price_calculate ?? $p->selling_price,
                    'stock_calculate' => $p->stock_calculate,
                    'is_non_stock' => $p->is_non_stock,
                    'category_id' => $p->category_id,
                    'category_name' => $p->category?->name ?? '',
                    'hero_image' => $p->hero_image,
                ])
                ->toArray(),
            'categories' => Category::query()
                ->select('id', 'name')
                ->get()
                ->toArray(),
            'members' => Member::query()
                ->select('id', 'name')
                ->get()
                ->toArray(),
            'payment_methods' => PaymentMethod::query()
                ->select('id', 'name', 'is_credit', 'payment_type')
                ->where('is_active', true)
                ->get()
                ->toArray(),
            'about' => About::first()?->toArray() ?? [],
        ];
    }
}