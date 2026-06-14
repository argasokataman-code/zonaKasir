/**
 * SyncManager — Background sync for offline operations
 *
 * When online, pushes pending_sales and queued_operations from IndexedDB to server.
 * Uses exponential backoff for retries. Polls every 30s.
 */
class SyncManager {
  constructor() {
    this.isSyncing = false;
    this.listeners = [];
    this.retryDelays = [5000, 15000, 30000, 60000, 120000];
  }

  get isOnline() {
    return navigator.onLine;
  }

  /**
   * Register event listeners for online/offline transitions.
   */
  init() {
    window.addEventListener('online', () => {
      console.log('[Sync] Online detected, starting sync...');
      this.updateUI(true);
      this.syncAll();
    });

    window.addEventListener('offline', () => {
      console.log('[Sync] Offline detected');
      this.updateUI(false);
    });

    this.updateUI(this.isOnline);

    // Poll sync every 30s when online
    setInterval(() => {
      if (this.isOnline && !this.isSyncing) {
        this.syncAll();
      }
    }, 30000);
  }

  /**
   * Sync all pending data: sales + queued operations.
   */
  async syncAll() {
    if (this.isSyncing || !this.isOnline) return;

    const om = window.offlineManager;
    if (!om || !om.db) return;

    this.isSyncing = true;
    this.emit('sync-start', {});

    const salesResult = await this.syncPendingSales();
    const opsResult = await this.syncQueuedOperations();

    this.isSyncing = false;
    this.emit('sync-complete', { sales: salesResult, operations: opsResult });
    this.emit('pending-updated', await om.getPendingCount());
  }

  /**
   * Sync all pending sales to the server.
   */
  async syncPendingSales() {
    const om = window.offlineManager;
    if (!om || !om.db) return { synced: 0, failed: 0, total: 0 };

    const pending = await om.getPendingSales();
    if (pending.length === 0) return { synced: 0, failed: 0, total: 0 };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let synced = 0;
    let failed = 0;

    for (const sale of pending) {
      try {
        const response = await fetch('/api/sync/submit', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          credentials: 'same-origin',
          body: JSON.stringify(sale),
        });

        if (response.ok) {
          await om.markSaleSynced(sale.temp_id);
          synced++;
        } else {
          // Fall back to original selling endpoint
          const fallbackResp = await fetch('/api/transaction/selling', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            credentials: 'same-origin',
            body: JSON.stringify(sale),
          });
          if (fallbackResp.ok) {
            await om.markSaleSynced(sale.temp_id);
            synced++;
          } else {
            const data = await fallbackResp.json().catch(() => ({}));
            await om.markSaleFailed(sale.temp_id, data.message || `HTTP ${fallbackResp.status}`);
            failed++;
          }
        }
      } catch (error) {
        await om.markSaleFailed(sale.temp_id, error.message);
        failed++;
      }
    }

    return { synced, failed, total: pending.length };
  }

  /**
   * Sync queued operations (member create, stock changes, etc.)
   */
  async syncQueuedOperations() {
    const om = window.offlineManager;
    if (!om || !om.db) return { synced: 0, failed: 0, total: 0 };

    const pending = await om.getQueuedOperations();
    if (pending.length === 0) return { synced: 0, failed: 0, total: 0 };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let synced = 0;
    let failed = 0;

    for (const op of pending) {
      // Skip if too many retries
      if (op.retries >= this.retryDelays.length) {
        failed++;
        continue;
      }

      try {
        const response = await fetch(op.endpoint, {
          method: op.method || 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          credentials: 'same-origin',
          body: JSON.stringify(op.payload),
        });

        if (response.ok) {
          await om.markOperationSynced(op.op_id);
          synced++;
        } else if (response.status >= 400 && response.status < 500) {
          // Client error — don't retry, mark as failed
          await om.markOperationFailed(op.op_id, `HTTP ${response.status}`);
          failed++;
        } else {
          // Server error — will retry later
          await om.markOperationFailed(op.op_id, `HTTP ${response.status}`);
          failed++;
        }
      } catch (error) {
        await om.markOperationFailed(op.op_id, error.message);
        failed++;
      }
    }

    return { synced, failed, total: pending.length };
  }

  /**
   * Get pending counts.
   */
  async getPendingCount() {
    const om = window.offlineManager;
    if (!om || !om.db) return 0;
    return om.getPendingCount();
  }

  async getQueuedCount() {
    const om = window.offlineManager;
    if (!om || !om.db) return 0;
    return om.getQueuedCount();
  }

  async getTotalPending() {
    const sales = await this.getPendingCount();
    const ops = await this.getQueuedCount();
    return sales + ops;
  }

  // ─── Event Emitter ─────────────────────────────────────────

  on(event, callback) {
    this.listeners.push({ event, callback });
  }

  emit(event, data) {
    this.listeners
      .filter(l => l.event === event)
      .forEach(l => l.callback(data));

    // Also dispatch on window so external listeners (e.g. OfflineIndicator) receive it
    window.dispatchEvent(new CustomEvent(event, { detail: data }));
  }

  // ─── UI Updates ────────────────────────────────────────────

  updateUI(isOnline) {
    window.dispatchEvent(new CustomEvent('connection-change', {
      detail: { online: isOnline }
    }));

    this.getTotalPending().then(count => {
      window.dispatchEvent(new CustomEvent('pending-sync-updated', {
        detail: { count }
      }));
    });
  }
}

window.syncManager = new SyncManager();
