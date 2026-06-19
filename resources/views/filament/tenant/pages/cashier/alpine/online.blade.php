window.__cashierOnline = () => ({
  cartQty: {{ Js::from(collect($cartItems)->pluck('qty', 'product_id')->toArray()) }},
  debounceTimers: {},

  instantAdd(productId) {
    this.cartQty[productId] = (this.cartQty[productId] || 0) + 1;
    clearTimeout(this.debounceTimers[productId]);
    this.debounceTimers[productId] = setTimeout(() => {
      if (navigator.onLine) {
        $wire.addCart(productId, { _bulk: this.cartQty[productId] });
      }
    }, 300);
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
    }, 300);
  },

  handleCartDataUpdated(event) {
    const raw = event.detail;
    const data = Array.isArray(raw) ? raw[0] : raw;
    this.cartQty = data?.cartItems || {};
  },
});
