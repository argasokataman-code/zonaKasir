window.__cashierOffline = () => ({
  offlineProducts: [],
  offlineCategories: [],
  offlineCart: {},
  offlineSelectedCategory: null,
  offlineSearch: '',
  offlineDb: null,
  paymentModalOpen: false,
  selectedPaymentMethod: null,
  offlineCartNote: '',
  offlineCartDiscount: 0,
  offlinePaymentMethod: 'cash',

  async initOfflineDb() {
    if (this.offlineDb) return this.offlineDb;
    return new Promise((resolve, reject) => {
      const req = indexedDB.open('zonakasir_offline', 2);
      req.onsuccess = (e) => { this.offlineDb = e.target.result; resolve(this.offlineDb); };
      req.onerror = (e) => reject(e.target.error);
      req.onupgradeneeded = (e) => {
        const db = e.target.result;
        ['products','categories','members','payment_methods','about','settings','pending_sales','meta'].forEach(n => {
          if (!db.objectStoreNames.contains(n)) db.createObjectStore(n, { keyPath: n === 'pending_sales' ? 'temp_id' : n === 'settings' ? 'key' : n === 'meta' ? 'key' : 'id' });
        });
      };
    });
  },

  async loadOfflineData() {
    try {
      const db = await this.initOfflineDb();
      const tx = db.transaction('products', 'readonly');
      const req = tx.objectStore('products').getAll();
      req.onsuccess = () => { this.offlineProducts = req.result || []; };
    } catch(e) { console.error('[Offline] Load products error:', e); }
    try {
      const db = await this.initOfflineDb();
      const tx = db.transaction('categories', 'readonly');
      const req = tx.objectStore('categories').getAll();
      req.onsuccess = () => { this.offlineCategories = req.result || []; };
    } catch(e) { console.error('[Offline] Load categories error:', e); }
  },

  get filteredOfflineProducts() {
    let filtered = this.offlineProducts;
    if (this.offlineSelectedCategory) filtered = filtered.filter(p => p.category_id === this.offlineSelectedCategory);
    if (this.offlineSearch) {
      const q = this.offlineSearch.toLowerCase();
      filtered = filtered.filter(p => (p.name && p.name.toLowerCase().includes(q)) || (p.sku && p.sku.toLowerCase().includes(q)) || (p.barcode && p.barcode.toLowerCase().includes(q)));
    }
    return filtered;
  },

  offlineAddToCart(productId) {
    const p = this.offlineProducts.find(x => x.id === productId);
    if (!p || (!p.is_non_stock && (p.stock_calculate !== undefined ? p.stock_calculate : p.stock || 0) <= 0)) return;
    if (!this.offlineCart[productId]) this.offlineCart[productId] = { id: productId, name: p.name, price: p.selling_price_calculate || p.selling_price || 0, qty: 0, discount_price: 0 };
    this.offlineCart[productId].qty++;
  },

  offlineRemoveFromCart(productId) {
    if (!this.offlineCart[productId]) return;
    this.offlineCart[productId].qty--;
    if (this.offlineCart[productId].qty <= 0) delete this.offlineCart[productId];
  },

  get offlineCartCount() {
    return Object.values(this.offlineCart).reduce((sum, item) => sum + item.qty, 0);
  },

  get offlineCartSubtotal() {
    return Object.values(this.offlineCart).reduce((sum, item) => sum + (item.price * item.qty), 0);
  },

  async saveOfflineSale() {
    const db = await this.initOfflineDb();
    const tx = db.transaction('pending_sales', 'readwrite');
    const entry = {
      temp_id: 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
      products: Object.values(this.offlineCart),
      total_price: this.offlineCartSubtotal - this.offlineCartDiscount,
      total_qty: this.offlineCartCount,
      payed_money: 0,
      money_changes: 0,
      discount_price: this.offlineCartDiscount,
      note: this.offlineCartNote,
      payment_method_id: this.selectedPaymentMethod,
      status: 'pending',
      created_at: new Date().toISOString(),
      synced: false
    };
    tx.objectStore('pending_sales').put(entry);
    this.offlineCart = {};
    this.offlineCartDiscount = 0;
    this.offlineCartNote = '';
    this.paymentModalOpen = false;
  },

  async runStartupSync() {
    if (!this.isPWA || !navigator.onLine) return;
    let db;
    try {
      db = await this.initOfflineDb();
    } catch(e) {
      console.error('[PWA] IndexedDB unavailable, skipping sync:', e);
      return;
    }
    try {
      const tx = db.transaction('meta', 'readonly');
      const req = tx.objectStore('meta').get('last_prefetch');
      const lastPrefetch = await new Promise(r => { req.onsuccess = () => r(req.result); req.onerror = () => r(null); });
      if (lastPrefetch && lastPrefetch.value) {
        const age = Date.now() - new Date(lastPrefetch.value).getTime();
        if (age < 30 * 60 * 1000) {
          console.log('[PWA] Skipping sync, data is fresh');
          this.loadOfflineData();
          return;
        }
      }
    } catch(e) {}
    this.showSyncSplash = true;
    this.syncProgress = 0;
    this.syncStatus = 'Syncing data...';
    try {
      // Use $wire.call to fetch via Livewire (server has auth context)
      const data = await $wire.call('getOfflineSyncData');
      this.syncProgress = 60;
      this.syncStatus = 'Caching data...';
      const stores = [
        { key: 'products', items: data.products || [] },
        { key: 'categories', items: data.categories || [] },
        { key: 'members', items: data.members || [] },
        { key: 'payment_methods', items: data.payment_methods || [] },
        { key: 'about', items: data.about ? (Array.isArray(data.about) ? data.about : [data.about]) : [] },
      ];
      for (const store of stores) {
        const tx = db.transaction(store.key, 'readwrite');
        const objStore = tx.objectStore(store.key);
        objStore.clear();
        for (const item of store.items) {
          objStore.put(item);
        }
        await new Promise((resolve, reject) => {
          tx.oncomplete = () => resolve();
          tx.onerror = () => reject(tx.error);
        });
      }
      const metaTx = db.transaction('meta', 'readwrite');
      metaTx.objectStore('meta').put({ key: 'last_prefetch', value: new Date().toISOString() });
      await new Promise(r => { metaTx.oncomplete = r; });
      this.syncProgress = 100;
      this.syncStatus = 'Ready!';
      await new Promise(r => setTimeout(r, 500));
      this.showSyncSplash = false;
      this.loadOfflineData();
    } catch(e) {
      console.error('[PWA] Sync failed:', e);
      // Fallback: save server-rendered data already on page to IndexedDB
      try {
        var serverProducts = window.__initialProducts;
        var serverCategories = window.__initialCategories;
        if ((serverProducts && serverProducts.length) || (serverCategories && serverCategories.length)) {
          this.syncStatus = 'Using page data...';
          var fallbackStores = [
            { key: 'products', items: serverProducts || [] },
            { key: 'categories', items: serverCategories || [] },
          ];
          for (var s of fallbackStores) {
            if (!s.items.length) continue;
            var txn = db.transaction(s.key, 'readwrite');
            var os = txn.objectStore(s.key);
            os.clear();
            s.items.forEach(function(it) { os.put(it); });
            await new Promise(function(res, rej) { txn.oncomplete = res; txn.onerror = rej; });
          }
          var mTx = db.transaction('meta', 'readwrite');
          mTx.objectStore('meta').put({ key: 'last_prefetch', value: new Date().toISOString() });
          await new Promise(function(r) { mTx.oncomplete = r; });
          console.log('[PWA] Fallback cache from page data OK');
          this.loadOfflineData();
        }
      } catch(fbErr) {
        console.error('[PWA] Fallback cache failed:', fbErr);
      }
      this.syncStatus = 'Sync failed';
      await new Promise(r => setTimeout(r, 1000));
      this.showSyncSplash = false;
    }
  },
});
