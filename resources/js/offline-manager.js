/**
 * OfflineManager — IndexedDB-based offline data layer for zonaKasir PWA
 *
 * Stores products, categories, members, payment methods, settings,
 * pending sales transactions, and queued operations.
 * Supports prefetch, API cache, staleness detection, and sync.
 */
class OfflineManager {
  constructor() {
    this.dbName = 'zonakasir_offline';
    this.dbVersion = 2;
    this.db = null;
    this.prefetchPromise = null;
    this.API_ENDPOINTS = {
      products: '/api/master/product',
      categories: '/api/master/category',
      members: '/api/master/member',
      payment_methods: '/api/master/payment-method',
      about: '/api/about',
    };
    this.BULK_SYNC_URL = '/api/sync/data';
  }

  async init() {
    if (this.db) return this.db;

    return new Promise((resolve, reject) => {
      const request = indexedDB.open(this.dbName, this.dbVersion);

      request.onerror = () => reject(request.error);

      request.onsuccess = () => {
        this.db = request.result;
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // Master data stores (read-only, synced from server)
        const masterStores = ['products', 'categories', 'members', 'payment_methods', 'about'];
        masterStores.forEach(storeName => {
          if (!db.objectStoreNames.contains(storeName)) {
            const store = db.createObjectStore(storeName, { keyPath: 'id' });
            if (storeName === 'products') {
              store.createIndex('category_id', 'category_id');
              store.createIndex('name', 'name');
            }
          }
        });

        // Settings store (key-value)
        if (!db.objectStoreNames.contains('settings')) {
          db.createObjectStore('settings', { keyPath: 'key' });
        }

        // Pending sales (created offline, synced later)
        if (!db.objectStoreNames.contains('pending_sales')) {
          const store = db.createObjectStore('pending_sales', { keyPath: 'temp_id' });
          store.createIndex('status', 'status');
          store.createIndex('created_at', 'created_at');
        }

        // Queued operations (generic offline writes)
        if (!db.objectStoreNames.contains('queued_operations')) {
          const store = db.createObjectStore('queued_operations', { keyPath: 'op_id' });
          store.createIndex('type', 'type');
          store.createIndex('created_at', 'created_at');
          store.createIndex('status', 'status');
        }

        // API response cache
        if (!db.objectStoreNames.contains('api_cache')) {
          const store = db.createObjectStore('api_cache', { keyPath: 'url' });
          store.createIndex('cached_at', 'cached_at');
        }

        // App metadata
        if (!db.objectStoreNames.contains('meta')) {
          db.createObjectStore('meta', { keyPath: 'key' });
        }
      };
    });
  }

  // ─── Generic CRUD ──────────────────────────────────────────

  async _getAll(storeName) {
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(storeName, 'readonly');
      const request = tx.objectStore(storeName).getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async _get(storeName, key) {
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(storeName, 'readonly');
      const request = tx.objectStore(storeName).get(key);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async _put(storeName, data) {
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(storeName, 'readwrite');
      const request = tx.objectStore(storeName).put(data);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async _delete(storeName, key) {
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(storeName, 'readwrite');
      const request = tx.objectStore(storeName).delete(key);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  async _clear(storeName) {
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(storeName, 'readwrite');
      const request = tx.objectStore(storeName).clear();
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  async _count(storeName) {
    return new Promise((resolve, reject) => {
      const tx = this.db.transaction(storeName, 'readonly');
      const request = tx.objectStore(storeName).count();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  // ─── Master Data ───────────────────────────────────────────

  async getProducts() { return this._getAll('products'); }
  async getProduct(id) { return this._get('products', id); }

  async getCategories() { return this._getAll('categories'); }
  async getMembers() { return this._getAll('members'); }
  async getPaymentMethods() { return this._getAll('payment_methods'); }
  async getAbout() { return this._getAll('about'); }

  async getSetting(key) {
    const result = await this._get('settings', key);
    return result?.value ?? null;
  }

  async setSetting(key, value) {
    return this._put('settings', { key, value });
  }

  // ─── Search (offline) ──────────────────────────────────────

  async searchProducts(query) {
    const products = await this.getProducts();
    if (!query) return products;
    const q = query.toLowerCase();
    return products.filter(p =>
      (p.name && p.name.toLowerCase().includes(q)) ||
      (p.barcode && p.barcode.toLowerCase().includes(q)) ||
      (p.sku && p.sku.toLowerCase().includes(q))
    );
  }

  async getProductsByCategory(categoryId) {
    const products = await this.getProducts();
    if (!categoryId) return products;
    return products.filter(p => p.category_id === categoryId);
  }

  // ─── Prefetch (online → IndexedDB) ─────────────────────────

  async prefetchMasterData() {
    if (this.prefetchPromise) return this.prefetchPromise;

    this.prefetchPromise = this._doPrefetch();
    return this.prefetchPromise;
  }

  async _doPrefetch() {
    const results = {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Try bulk sync endpoint first (more efficient, single request)
    try {
      const bulkResp = await fetch(this.BULK_SYNC_URL, {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
      });

      if (bulkResp.ok) {
        const bulkData = await bulkResp.json();
        const data = bulkData.data || bulkData;

        for (const [key, storeName] of Object.entries({
          products: 'products',
          categories: 'categories',
          members: 'members',
          payment_methods: 'payment_methods',
        })) {
          const items = data[key];
          if (Array.isArray(items) && items.length > 0) {
            await this.syncStore(storeName, items);
            results[storeName] = items.length;
          }
        }

        // About (may be null or object)
        if (data.about) {
          await this.syncStore('about', [data.about]);
          results.about = 1;
        }

        // Settings
        if (data.settings) {
          for (const [k, v] of Object.entries(data.settings)) {
            await this.setSetting(k, v);
          }
          results.settings = Object.keys(data.settings).length;
        }

        await this.setMeta('last_prefetch', new Date().toISOString());
        await this.setMeta('prefetch_results', JSON.stringify(results));
        this.prefetchPromise = null;
        return results;
      }
    } catch (err) {
      console.warn('[OfflineManager] Bulk sync failed, falling back to individual endpoints:', err.message);
    }

    for (const [storeName, url] of Object.entries(this.API_ENDPOINTS)) {
      try {
        const response = await fetch(url, {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
          },
          credentials: 'same-origin',
        });

        if (!response.ok) {
          console.warn(`[OfflineManager] Prefetch ${storeName} failed: HTTP ${response.status}`);
          continue;
        }

        const data = await response.json();
        var items;
        if (Array.isArray(data)) {
          items = data;
        } else if (data && typeof data === 'object' && data.id) {
          // Single object response (e.g. /api/about) — wrap in array
          items = [data];
        } else {
          items = data.data || [];
        }

        if (items.length > 0) {
          await this.syncStore(storeName, items);
          results[storeName] = items.length;
        }
      } catch (err) {
        console.warn(`[OfflineManager] Prefetch ${storeName} error:`, err.message);
      }
    }

    await this.setMeta('last_prefetch', new Date().toISOString());
    await this.setMeta('prefetch_results', JSON.stringify(results));

    this.prefetchPromise = null;
    return results;
  }

  // ─── Bulk Sync (replace all data) ─────────────────────────

  async syncStore(storeName, items) {
    await this._clear(storeName);
    for (const item of items) {
      await this._put(storeName, item);
    }
  }

  // ─── API Response Cache ────────────────────────────────────

  async cacheApiResponse(url, data) {
    return this._put('api_cache', {
      url,
      data,
      cached_at: new Date().toISOString(),
    });
  }

  async getCachedApiResponse(url) {
    const result = await this._get('api_cache', url);
    return result?.data ?? null;
  }

  async isCacheStale(url, maxAgeMs) {
    const result = await this._get('api_cache', url);
    if (!result) return true;
    const age = Date.now() - new Date(result.cached_at).getTime();
    return age > (maxAgeMs || 30 * 60 * 1000);
  }

  // ─── Pending Sales ─────────────────────────────────────────

  async addPendingSale(sale) {
    const entry = {
      ...sale,
      temp_id: sale.temp_id || 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
      status: 'pending',
      created_at: sale.created_at || new Date().toISOString(),
      synced: false,
    };
    await this._put('pending_sales', entry);
    return entry;
  }

  async getPendingSales() {
    const all = await this._getAll('pending_sales');
    return all.filter(s => s.status === 'pending');
  }

  async markSaleSynced(temp_id) {
    const sale = await this._get('pending_sales', temp_id);
    if (sale) {
      sale.status = 'synced';
      sale.synced_at = new Date().toISOString();
      await this._put('pending_sales', sale);
    }
  }

  async markSaleFailed(temp_id, error) {
    const sale = await this._get('pending_sales', temp_id);
    if (sale) {
      sale.status = 'failed';
      sale.error = error;
      await this._put('pending_sales', sale);
    }
  }

  async deletePendingSale(temp_id) {
    return this._delete('pending_sales', temp_id);
  }

  async getPendingCount() {
    const sales = await this.getPendingSales();
    return sales.length;
  }

  async clearSyncedSales() {
    const all = await this._getAll('pending_sales');
    for (const sale of all) {
      if (sale.status === 'synced' || sale.status === 'failed') {
        await this._delete('pending_sales', sale.temp_id);
      }
    }
  }

  // ─── Queued Operations (generic offline writes) ────────────

  async queueOperation(type, payload, endpoint, method) {
    const op_id = 'op_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    const entry = {
      op_id,
      type,
      payload,
      endpoint,
      method: method || 'POST',
      status: 'pending',
      retries: 0,
      created_at: new Date().toISOString(),
    };
    await this._put('queued_operations', entry);
    return entry;
  }

  async getQueuedOperations() {
    const all = await this._getAll('queued_operations');
    return all.filter(op => op.status === 'pending');
  }

  async markOperationSynced(op_id) {
    const op = await this._get('queued_operations', op_id);
    if (op) {
      op.status = 'synced';
      op.synced_at = new Date().toISOString();
      await this._put('queued_operations', op);
    }
  }

  async markOperationFailed(op_id, error) {
    const op = await this._get('queued_operations', op_id);
    if (op) {
      op.status = 'failed';
      op.error = error;
      op.retries = (op.retries || 0) + 1;
      await this._put('queued_operations', op);
    }
  }

  async deleteOperation(op_id) {
    return this._delete('queued_operations', op_id);
  }

  async getQueuedCount() {
    const ops = await this.getQueuedOperations();
    return ops.length;
  }

  // ─── Meta ──────────────────────────────────────────────────

  async getMeta(key) {
    const result = await this._get('meta', key);
    return result?.value ?? null;
  }

  async setMeta(key, value) {
    return this._put('meta', { key, value });
  }

  // ─── Statistics ────────────────────────────────────────────

  async getStats() {
    return {
      products: await this._count('products'),
      categories: await this._count('categories'),
      members: await this._count('members'),
      payment_methods: await this._count('payment_methods'),
      pending_sales: await this.getPendingCount(),
      queued_operations: await this.getQueuedCount(),
      last_prefetch: await this.getMeta('last_prefetch'),
    };
  }

  async clearAll() {
    const stores = ['products', 'categories', 'members', 'payment_methods', 'about', 'settings', 'api_cache', 'meta'];
    for (const store of stores) {
      await this._clear(store);
    }
  }
}

// Singleton
window.offlineManager = new OfflineManager();
