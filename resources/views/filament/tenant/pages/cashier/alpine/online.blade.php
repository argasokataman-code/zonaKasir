window.__cashierOnline = () => ({
  cartQty: {{ Js::from(collect($cartItems)->pluck('qty', 'product_id')->toArray()) }},
  subTotal: {{ $sub_total }},
  totalPrice: {{ $total_price }},
  cartCount: {{ $cartCount }},
  debounceTimers: {},

  instantAdd(productId) {
    this.cartQty[productId] = (this.cartQty[productId] || 0) + 1;
    clearTimeout(this.debounceTimers[productId]);
    this.debounceTimers[productId] = setTimeout(() => {
      if (navigator.onLine) {
        $wire.addCart(productId, { _bulk: this.cartQty[productId] });
      }
    }, 100);
  },

  instantReduce(productId) {
    if ((this.cartQty[productId] || 0) > 0) {
      this.cartQty[productId]--;
      if (this.cartQty[productId] <= 0) delete this.cartQty[productId];
    }
    clearTimeout(this.debounceTimers[productId]);
    this.debounceTimers[productId] = setTimeout(() => {
      if (navigator.onLine) {
        const qty = this.cartQty[productId] || 0;
        $wire.addCart(productId, { _bulk: qty });
      }
    }, 100);
  },

  handleCartDataUpdated(event) {
    const raw = event.detail;
    const data = Array.isArray(raw) ? raw[0] : raw;
    const serverCart = data?.cartItems || {};
    const localQty = { ...this.cartQty };
    // Merge server data without resetting cartQty (avoids flicker).
    // Items NOT in serverCart were deleted — remove them.
    for (const id of Object.keys(localQty)) {
      if (!(id in serverCart)) delete this.cartQty[id];
    }
    for (const [id, qty] of Object.entries(serverCart)) {
      this.cartQty[id] = Math.max(qty, localQty[id] || 0);
    }
    // Replace cart sidebar HTML with server-rendered content
    if (data.cartHtml) {
      const container = document.getElementById('cart-items-container');
      if (container) container.innerHTML = data.cartHtml;
    }
    // Update totals via Alpine
    if (data.subTotal !== undefined) this.subTotal = data.subTotal;
    if (data.totalPrice !== undefined) this.totalPrice = data.totalPrice;
    if (data.cartCount !== undefined) this.cartCount = data.cartCount;
  },
});
