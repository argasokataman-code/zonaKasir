<?php

namespace App\Filament\Tenant\Pages\Traits;

use App\Models\Tenants\CartItem;
use App\Models\Tenants\Product;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Livewire\Attributes\Renderless;

trait CartInteraction
{
    /**
     * Zero-Livewire-refresh: only dispatch Alpine event.
     * Do NOT update any Livewire property — that triggers full template render.
     * Alpine manages display (badges, totals, count) via cart-data-updated.
     */
    private function softRefresh(Product $product): void
    {
        // Re-query affected item to keep $this->cartItems in sync
        // (still needed for Livewire form bindings and full refresh).
        $updated = CartItem::where('product_id', $product->getKey())
            ->with(['product:id,name,sku', 'priceUnit:id,selling_price'])
            ->cashier()->first();
        $this->cartItems = $this->cartItems->reject(fn ($i) => (int) $i->product_id === $product->getKey());
        if ($updated) $this->cartItems->push($updated);
        $this->cartCount = $this->cartItems->count();
        $this->calculateTotalPrice();

        $this->dispatch('cart-data-updated', [
            'cartItems' => $this->cartItems->pluck('qty', 'product_id')->toArray(),
            'cartCount' => $this->cartCount,
            'subTotal' => $this->sub_total,
            'totalPrice' => $this->total_price,
        ]);
    }
    private function validateStock(Product $product, $qty): bool
    {
        $available = $product->stock_calculate;
        if (! $product->is_non_stock && ($available < 0 || $available < $qty)) {
            Notification::make()
                ->title(__('Stock is out'))
                ->danger()
                ->send();

            return false;
        }

        return true;
    }

    #[Renderless]
    public function addCart(Product $product, ?array $data = null)
    {
        $auth = Filament::auth()->id();

        if (! $data) {
            $qty = (
                CartItem::whereProductId($product->getKey())
                    ->select('id', 'qty')
                    ->cashier()
                    ->first()?->qty ?? 0
            ) + 1;
        } elseif (isset($data['_bulk'])) {
            $qty = (int) $data['_bulk'];
            if ($qty <= 0) {
                CartItem::where('product_id', $product->getKey())->cashier()->delete();
                $this->softRefresh($product);
                return;
            }
        } elseif (! $data['amount']) {
            $this->deleteCart($product);
            return;
        } else {
            $qty = (int) $data['amount'];
        }

        if (! $this->validateStock($product, $qty)) {
            return;
        }

        CartItem::query()
            ->updateOrCreate(
                [
                    'product_id' => $product->getKey(),
                    'user_id' => $auth,
                ],
                [
                    'qty' => $qty,
                    'price' => $product->selling_price * $qty,
                ]
            );

        $this->softRefresh($product);
    }

    #[Renderless]
    public function reduceCart(Product $product)
    {
        $cartItem = CartItem::whereProductId($product->getKey())
            ->select('id', 'qty', 'product_id', 'price')
            ->cashier()
            ->first();
        if (! $cartItem) {
            $this->softRefresh($product);
            return;
        }
        $qty = $cartItem->qty - 1;
        if ($qty == 0) {
            $cartItem->delete();
            $this->softRefresh($product);
            return;
        }
        $price = $product->selling_price * ($qty);
        $cartItem->fill([
            'qty' => $qty,
            'price' => $price,
        ]);
        $cartItem->save();
        $this->softRefresh($product);
    }

    #[Renderless]
    public function deleteCart(Product $product)
    {
        CartItem::where('product_id', $product->getKey())->cashier()->delete();
        $this->softRefresh($product);
    }

    public function addDiscountPricePerItem(CartItem $cartItem, $value)
    {
        if (!($value && $value > 0) || $value > $cartItem->product->selling_price) {
            return;
        }
        $cartItem->discount_price = (float) $value;
        $cartItem->save();
        $this->refreshCart();
    }

    public function updateCart(CartItem $cartItem, $value)
    {
        if ((int) $value == 0) {
            $cartItem->delete();
            $this->refreshCart();

            return;
        }
        $value = $value != '' ? $value : 0;
        if (! $cartItem->product->is_non_stock && $cartItem->product->stock_calculate <= $value) {
            Notification::make()
                ->title(__('Stock is out'))
                ->danger()
                ->send();
            $this->refreshCart();

            return;
        }
        $price = $cartItem->product->selling_price * ((int) $value);
        $cartItem->fill([
            'qty' => $value,
            'price' => $price,
        ]);
        $cartItem->save();
        $this->refreshCart();
    }

    public function clearCart()
    {
        CartItem::query()
            ->cashier()
            ->delete();

        Notification::make()
            ->title(__('Cart has been cleared'))
            ->success()
            ->send();
        $this->refreshCart();
    }

    public function addCartUsingScanner(string $value)
    {
        $product = Product::findByBarcodeOrSku($value);

        if (! $product) {
            Notification::make()
                ->title(__('Product not found'))
                ->warning()
                ->send();

            return;
        }

        $stock = 1;

        $cartItem = CartItem::whereProductId($product->getKey())
            ->select('id', 'qty')
            ->cashier()
            ->first();
        if ($cartItem) {
            $stock = $cartItem->qty + 1;
        }

        $this->addCart($product, [
            'amount' => $stock,
        ]);
    }
}
