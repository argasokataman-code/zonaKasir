/**
 * OfflineIndicator — Floating UI for offline status + pending sync badge
 *
 * Shows a persistent banner when offline with retry attempt counter,
 * a floating badge with pending count, and connection transition animations.
 *
 * Connection retry: attempts 1→4 with exponential backoff (3s, 6s, 12s, 24s).
 * On reconnect: toast "Connection restored" and auto-sync.
 */
class OfflineIndicator {
  constructor() {
    this.banner = null;
    this.badge = null;
    this.isOnline = navigator.onLine;
    this.syncStatus = null;
    this.reconnectAttempts = 0;
    this.maxReconnectAttempts = 4;
    this.reconnectDelays = [3000, 6000, 12000, 24000];
    this.reconnectTimer = null;
    this.reconnectProbeTimer = null;
  }

  init() {
    this.createBanner();
    this.createBadge();
    this.bindEvents();
    this.updateStatus(this.isOnline);

    // Start reconnect loop if already offline on load
    if (!this.isOnline) {
      this.startReconnectLoop();
    }
  }

  createBanner() {
    if (this.banner) return;

    var banner = document.createElement('div');
    banner.id = 'offline-banner';
    banner.innerHTML =
      '<div style="display:flex;align-items:center;justify-content:space-between;padding:8px 16px;">' +
        '<div style="display:flex;align-items:center;gap:8px;">' +
          '<span id="offline-banner-dot" style="width:8px;height:8px;border-radius:50%;background:#ef4444;display:inline-block;flex-shrink:0;"></span>' +
          '<span id="offline-banner-text" style="font-size:13px;font-weight:500;color:#fff;">Check your connection</span>' +
        '</div>' +
        '<div style="display:flex;align-items:center;gap:8px;">' +
          '<span id="offline-banner-attempt" style="font-size:11px;color:rgba(255,255,255,0.7);"></span>' +
          '<span id="offline-banner-pending" style="font-size:12px;color:rgba(255,255,255,0.8);"></span>' +
          '<button id="offline-sync-btn" style="background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.4);color:#fff;padding:4px 12px;border-radius:6px;font-size:12px;cursor:pointer;display:none;">Retry</button>' +
        '</div>' +
      '</div>';

    banner.style.cssText =
      'display:none;position:fixed;top:0;left:0;right:0;z-index:99998;' +
      'background:#1f2937;color:#fff;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;' +
      'box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:transform 0.3s ease;';

    document.body.appendChild(banner);
    this.banner = banner;

    var self = this;
    var syncBtn = document.getElementById('offline-sync-btn');
    if (syncBtn) {
      syncBtn.onclick = function() {
        if (window.syncManager && navigator.onLine) {
          syncBtn.textContent = 'Syncing...';
          syncBtn.disabled = true;
          window.syncManager.syncAll().then(function() {
            syncBtn.textContent = 'Retry';
            syncBtn.disabled = false;
          });
        } else if (!navigator.onLine) {
          // Manual retry when offline
          self.reconnectAttempts = 0;
          self.reconnectTimer = null;
          syncBtn.style.display = 'none';
          self.banner.querySelector('#offline-banner-text').textContent = '⚠️ Check your connection';
          self.startReconnectLoop();
        }
      };
    }
  }

  createBadge() {
    if (this.badge) return;

    var badge = document.createElement('div');
    badge.id = 'sync-badge';
    badge.innerHTML =
      '<div style="display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:20px;' +
      'background:#1f2937;color:#fff;font-size:12px;font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,0.25);cursor:pointer;"' +
      ' title="Pending sync items">' +
        '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
          '<path d="M21 12a9 9 0 11-6.22-8.56"></path>' +
          '<path d="M21 3v6h-6"></path>' +
        '</svg>' +
        '<span id="sync-badge-count">0</span>' +
      '</div>';

    badge.style.cssText =
      'display:none;position:fixed;bottom:80px;right:16px;z-index:99997;' +
      'font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;' +
      'transition:all 0.3s ease;transform:scale(0);';

    document.body.appendChild(badge);
    this.badge = badge;

    badge.addEventListener('click', function() {
      if (window.syncManager && navigator.onLine) {
        window.syncManager.syncAll();
      }
    });
  }

  bindEvents() {
    var self = this;

    window.addEventListener('connection-change', function(e) {
      self.updateStatus(e.detail.online);
    });

    window.addEventListener('pending-sync-updated', function(e) {
      self.updateBadgeCount(e.detail.count);
    });

    window.addEventListener('sync-start', function() {
      self.showSyncAnimation(true);
    });

    window.addEventListener('sync-complete', function(e) {
      self.showSyncAnimation(false);
      var detail = e.detail || {};
      var total = (detail.sales || {}).synced || 0;
      total += (detail.operations || {}).synced || 0;
      if (total > 0) {
        self.showToast('Synced ' + total + ' item(s)', 'success');
      }
    });

    // Also listen to native online/offline for pages without SyncManager
    window.addEventListener('online', function() {
      self.updateStatus(true);
    });
    window.addEventListener('offline', function() {
      self.updateStatus(false);
    });
  }

  updateStatus(online) {
    var wasOffline = !this.isOnline;
    this.isOnline = online;

    if (online) {
      // ── Online ──
      this.stopReconnectLoop();

      if (wasOffline) {
        // Reconnection! Show green toast
        this.showToast('✅ Connection restored', 'success');
      }

      if (this.banner) {
        this.banner.style.display = 'none';
        this.banner.style.transform = 'translateY(-100%)';
      }
      this.resetAttemptUI();

      // Auto-sync on reconnect
      if (wasOffline && window.syncManager) {
        setTimeout(function() { window.syncManager.syncAll(); }, 1000);
      }
    } else {
      // ── Offline ──
      this.startReconnectLoop();

      if (this.banner) {
        this.banner.style.display = 'block';
        var self = this;
        setTimeout(function() { self.banner.style.transform = 'translateY(0)'; }, 10);
      }

      var dot = document.getElementById('offline-banner-dot');
      var text = document.getElementById('offline-banner-text');
      if (dot) dot.style.background = '#ef4444';
      if (text) text.textContent = '⚠️ Check your connection';
    }

    this.updateBadgeCount();
  }

  // ─── Connection Retry Loop ────────────────────────────────────

  startReconnectLoop() {
    if (this.reconnectTimer || this.reconnectProbeTimer) return; // already running

    var self = this;
    this.reconnectAttempts = 0;
    this.attemptReconnect();
  }

  attemptReconnect() {
    if (this.isOnline || this.reconnectAttempts >= this.maxReconnectAttempts) {
      this.showMaxAttemptsReached();
      return;
    }

    this.reconnectAttempts++;
    this.updateAttemptUI();

    var delay = this.reconnectDelays[this.reconnectAttempts - 1] || 24000;
    var self = this;

    // Probe the server
    this.reconnectProbeTimer = setTimeout(function() {
      if (navigator.onLine) {
        // OS says online — verify with a real fetch
        self.probeServer().then(function(ok) {
          if (ok) {
            self.updateStatus(true);
          } else {
            // Probe failed — use the backoff delay before retrying
            self.reconnectTimer = setTimeout(function() {
              self.attemptReconnect();
            }, delay);
          }
        });
      } else {
        // Still offline at OS level — wait then retry
        self.reconnectTimer = setTimeout(function() {
          self.attemptReconnect();
        }, delay);
      }
    }, delay);
  }

  probeServer() {
    return fetch('/member', {
      method: 'HEAD',
      cache: 'no-store',
      credentials: 'same-origin'
    }).then(function(r) { return r.ok; }).catch(function() { return false; });
  }

  stopReconnectLoop() {
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer);
      this.reconnectTimer = null;
    }
    if (this.reconnectProbeTimer) {
      clearTimeout(this.reconnectProbeTimer);
      this.reconnectProbeTimer = null;
    }
    this.reconnectAttempts = 0;
  }

  updateAttemptUI() {
    var attemptEl = document.getElementById('offline-banner-attempt');
    if (attemptEl) {
      attemptEl.textContent = 'Attempt ' + this.reconnectAttempts + '/' + this.maxReconnectAttempts;
    }

    var dot = document.getElementById('offline-banner-dot');
    if (dot) {
      dot.style.background = '#f59e0b'; // yellow during retry
      dot.style.animation = 'pulse 1s infinite';
    }
  }

  resetAttemptUI() {
    this.reconnectAttempts = 0;
    var attemptEl = document.getElementById('offline-banner-attempt');
    if (attemptEl) attemptEl.textContent = '';

    var dot = document.getElementById('offline-banner-dot');
    if (dot) {
      dot.style.background = '#22c55e';
      dot.style.animation = 'none';
    }
  }

  showMaxAttemptsReached() {
    var text = document.getElementById('offline-banner-text');
    if (text) text.textContent = '⚠️ Could not reconnect — check your network';

    var attemptEl = document.getElementById('offline-banner-attempt');
    if (attemptEl) attemptEl.textContent = '';

    var retryBtn = document.getElementById('offline-sync-btn');
    if (retryBtn) {
      retryBtn.style.display = 'inline-block';
      retryBtn.textContent = 'Retry now';
      retryBtn.disabled = false;

      var self = this;
      retryBtn.onclick = function() {
        self.reconnectAttempts = 0;
        self.reconnectTimer = null;
        retryBtn.style.display = 'none';
        if (text) text.textContent = '⚠️ Check your connection';
        self.startReconnectLoop();
      };
    }
  }

  // ─── Badge ─────────────────────────────────────────────────────

  async updateBadgeCount(count) {
    if (count === undefined) {
      var om = window.offlineManager;
      if (om && om.db) {
        count = await om.getPendingCount() + await om.getQueuedCount();
      } else {
        count = 0;
      }
    }

    var badge = this.badge;
    var countEl = document.getElementById('sync-badge-count');

    if (count > 0) {
      badge.style.display = 'block';
      setTimeout(function() { badge.style.transform = 'scale(1)'; }, 10);
      if (countEl) countEl.textContent = count;
    } else {
      badge.style.transform = 'scale(0)';
      setTimeout(function() { badge.style.display = 'none'; }, 300);
    }

    var pendingEl = document.getElementById('offline-banner-pending');
    if (pendingEl && !this.isOnline) {
      pendingEl.textContent = count > 0 ? count + ' pending' : '';
    }

    var retryBtn = document.getElementById('offline-sync-btn');
    if (retryBtn && this.isOnline && count > 0) {
      retryBtn.style.display = 'inline-block';
      retryBtn.textContent = 'Sync now';
      retryBtn.disabled = false;
    }
  }

  // ─── Sync Animation ────────────────────────────────────────────

  showSyncAnimation(syncing) {
    var dot = document.getElementById('offline-banner-dot');
    if (!dot) return;

    if (syncing) {
      dot.style.background = '#f59e0b';
      dot.style.animation = 'pulse 1s infinite';
    } else {
      dot.style.background = this.isOnline ? '#22c55e' : '#ef4444';
      dot.style.animation = 'none';
    }
  }

  // ─── Toast ─────────────────────────────────────────────────────

  showToast(message, type) {
    var toast = document.createElement('div');
    var bgColor = type === 'success' ? '#22c55e' : type === 'error' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#3b82f6';
    toast.style.cssText =
      'position:fixed;bottom:140px;left:50%;transform:translateX(-50%);z-index:100000;' +
      'background:' + bgColor + ';color:#fff;padding:10px 20px;border-radius:8px;font-size:13px;' +
      'font-weight:500;box-shadow:0 4px 12px rgba(0,0,0,0.2);font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;' +
      'animation:fadeInOut 3s forwards;white-space:nowrap;';

    document.body.appendChild(toast);

    setTimeout(function() {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.3s';
      setTimeout(function() { toast.remove(); }, 300);
    }, 2700);
  }
}

window.offlineIndicator = new OfflineIndicator();

// Inject animation CSS
(function() {
  var style = document.createElement('style');
  style.textContent =
    '@keyframes pulse{0%,100%{opacity:1}50%{opacity:0.4}}' +
    '@keyframes fadeInOut{0%{opacity:0;transform:translateX(-50%) translateY(10px)}10%{opacity:1;transform:translateX(-50%) translateY(0)}80%{opacity:1}100%{opacity:0}}';
  document.head.appendChild(style);
})();
