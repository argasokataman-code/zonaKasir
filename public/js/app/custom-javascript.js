let selectedDevice = null;

// ─── Auth Error Interceptor (PWA Critical) ──────────────────
// Catches 401/403 from any fetch/Livewire call → auto-redirect to login
// Prevents stuck error modal in PWA where user can't refresh
(function() {
  var REDIRECTING = false;
  var LOGIN_PATHS = ['/member/login', '/admin/login'];

  function getLoginUrl() {
    var path = window.location.pathname;
    if (path.indexOf('/admin') === 0) return '/admin/login';
    return '/member/login';
  }

  function isLoginUrl(url) {
    return LOGIN_PATHS.some(function(p) { return url.indexOf(p) !== -1; });
  }

  function redirectLogin() {
    if (REDIRECTING) return;
    REDIRECTING = true;

    // Hide any Livewire error modal that already appeared
    document.querySelectorAll('[x-data]').forEach(function(el) {
      // Alpine.js v3 API
      if (el._x_dataStack && el._x_dataStack[0] && el._x_dataStack[0].open !== undefined) {
        el._x_dataStack[0].open = false;
      }
      // Alpine.js v2 API (fallback)
      if (el.__x && el.__x.$data && el.__x.$data.open !== undefined) {
        el.__x.$data.open = false;
      }
    });
    // Force-hide any fixed/modal overlays
    document.querySelectorAll('.fi-modal-window, [role="dialog"]').forEach(function(el) {
      el.style.display = 'none';
    });
    // Remove backdrop
    document.querySelectorAll('.fi-modal-window ~ div, .fixed.inset-0').forEach(function(el) {
      if (el.style.zIndex > 50) el.style.display = 'none';
    });

    // Clear SW session cache
    if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
      navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_SESSION' });
    }
    window.location.href = getLoginUrl();
  }

  // Intercept fetch API
  var origFetch = window.fetch;
  window.fetch = function() {
    return origFetch.apply(this, arguments).then(function(resp) {
      if ((resp.status === 401 || resp.status === 403) && !isLoginUrl(window.location.href)) {
        var url = (arguments[0] && arguments[0].url) ? arguments[0].url : String(arguments[0]);
        if (!isLoginUrl(url)) {
          redirectLogin();
        }
      }
      return resp;
    });
  };

  // Intercept XMLHttpRequest (used by some Livewire versions)
  var origXHROpen = XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open = function(method, url) {
    this._interceptUrl = url;
    return origXHROpen.apply(this, arguments);
  };

  var origXHRSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.send = function() {
    this.addEventListener('load', function() {
      if ((this.status === 401 || this.status === 403) && !isLoginUrl(window.location.href)) {
        var url = this._interceptUrl || '';
        if (!isLoginUrl(url)) {
          redirectLogin();
        }
      }
    });
    return origXHRSend.apply(this, arguments);
  };

  // Livewire specific: listen for auth errors in component responses
  if (typeof Livewire !== 'undefined') {
    Livewire.hook('request', function(resp) {
      if (resp && (resp.status === 401 || resp.status === 403)) {
        redirectLogin();
      }
    });
  }
})();

// ─── Livewire Error Modal Fix ─────────────────────────────
// Restyle Livewire's black blank dialog → white + message + close
(function() {
  // Inject CSS to restyle the dialog
  var style = document.createElement('style');
  style.textContent = `
    dialog#livewire-error {
      border: none !important;
      border-radius: 16px !important;
      padding: 0 !important;
      max-width: 380px !important;
      width: 90% !important;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3) !important;
    }
    dialog#livewire-error::backdrop {
      background: rgba(0,0,0,0.5) !important;
    }
    dialog#livewire-error iframe {
      display: none !important;
    }
  `;
  document.head.appendChild(style);

  // MutationObserver to replace dialog content
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      mutation.addedNodes.forEach(function(node) {
        var target = null;
        if (node.id === 'livewire-error') target = node;
        if (node.querySelector) target = node.querySelector('#livewire-error');
        if (!target) return;

        // Offline: remove modal immediately — no flickering
        if (!navigator.onLine) {
          target.remove();
          document.body.style.overflow = '';
          return;
        }

        // Wait for dialog to be ready, then replace content
        setTimeout(function() {
          // Hide iframe, show custom content
          var iframe = target.querySelector('iframe');
          if (iframe) iframe.style.display = 'none';

          // Check if we already replaced content
          if (target.querySelector('.custom-offline-content')) return;

          // Detect error type
          var isAuthError = false;
          try {
            if (iframe && iframe.contentDocument) {
              var html = iframe.contentDocument.body.innerHTML;
              isAuthError = html.includes('401') || html.includes('403') || html.includes('Unauthorized');
            }
          } catch(e) {}

          // Auth error → redirect to login
          if (isAuthError) {
            target.remove();
            window.location.href = window.location.pathname.indexOf('/admin') === 0 ? '/admin/login' : '/member/login';
            return;
          }

          // Network error → show custom message
          target.innerHTML = '<div class="custom-offline-content" style="background:#fff;border-radius:16px;padding:32px;text-align:center;font-family:-apple-system,BlinkMacSystemFont,sans-serif;">' +
            '<div style="width:56px;height:56px;background:#FEF3C7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;">📡</div>' +
            '<h2 style="margin:0 0 8px;font-size:18px;font-weight:700;color:#1f2937;">Koneksi Terputus</h2>' +
            '<p style="margin:0 0 20px;color:#6b7280;font-size:14px;line-height:1.5;">Tidak dapat terhubung ke server.<br>Periksa koneksi internet kamu.</p>' +
            '<button onclick="window.location.reload()" style="background:#FF6600;color:#fff;border:none;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;width:100%;">Coba Lagi</button>' +
            '</div>';

          // Remove body overflow hidden
          document.body.style.overflow = '';
        }, 100);
      });
    });
  });
  observer.observe(document.body, { childList: true, subtree: true });
})();

// ─── PWA Visibility Change — Session Refresh ─────────────────
// When user returns to PWA after idle, refresh session to prevent 401
(function() {
  if (!document.cookie.includes('session')) return;

  var LAST_HIDDEN = 0;
  var THRESHOLD_MS = 5 * 60 * 1000; // 5 min — if away longer, refresh session

  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      LAST_HIDDEN = Date.now();
      return;
    }

    // Page became visible — check how long we were away
    var awayMs = Date.now() - LAST_HIDDEN;
    if (awayMs < THRESHOLD_MS) return; // short switch, skip

    console.log('[PWA] Away ' + Math.round(awayMs / 1000) + 's — refreshing session');

    // Silent HEAD request to refresh session lifetime on server
    fetch(window.location.pathname, {
      method: 'HEAD',
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    }).then(function(resp) {
      if (resp.status === 401 || resp.status === 403) {
        // Session expired while away → redirect login
        var path = window.location.pathname;
        var loginUrl = path.indexOf('/admin') === 0 ? '/admin/login' : '/member/login';
        window.location.href = loginUrl;
      }
    }).catch(function() {});
  });

  // Also refresh on app focus (PWA specific)
  window.addEventListener('focus', function() {
    if (LAST_HIDDEN > 0) {
      var awayMs = Date.now() - LAST_HIDDEN;
      if (awayMs >= THRESHOLD_MS && navigator.onLine) {
        fetch(window.location.pathname, {
          method: 'HEAD',
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).catch(function() {});
      }
    }
  });
})();

// ─── PWA Offline Init ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  if (window.offlineManager) {
    window.offlineManager.init().then(function() {
      console.log('[PWA] OfflineManager initialized');
      if (window.offlineManager && navigator.onLine) {
        // Only prefetch if stale (> 30 min) or no data yet
        window.offlineManager.getMeta('last_prefetch').then(function(lastPrefetch) {
          var isStale = !lastPrefetch || (Date.now() - new Date(lastPrefetch).getTime() > 30 * 60 * 1000);
          if (isStale) {
            window.offlineManager.prefetchMasterData().catch(function() {
              console.log('[PWA] Prefetch skipped (not authenticated or offline)');
            });
          } else {
            console.log('[PWA] Data is fresh, skipping prefetch');
          }
        }).catch(function() {
          window.offlineManager.prefetchMasterData().catch(function() {});
        });
      }
    }).catch(function(err) {
      console.error('[PWA] OfflineManager init failed:', err);
    });
  }
  if (window.syncManager) {
    window.syncManager.init();
    console.log('[PWA] SyncManager initialized');
  }
  if (window.offlineIndicator) {
    window.offlineIndicator.init();
    console.log('[PWA] OfflineIndicator initialized');
  }
});

// Listen for SW-initiated data sync
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'SYNC_MASTER_DATA') {
      if (window.offlineManager && navigator.onLine) {
        window.offlineManager.prefetchMasterData().catch(function() {});
      }
    }

    // Background sync request from SW
    if (event.data && event.data.type === 'BACKGROUND_SYNC') {
      console.log('[PWA] Background sync triggered by SW');
      if (window.syncManager && navigator.onLine) {
        window.syncManager.syncAll();
      }
    }
  });
}

// ─── Background Sync Registration ────────────────────────────
(function() {
  if (!('serviceWorker' in navigator) || !('SyncManager' in window)) return;

  navigator.serviceWorker.ready.then(function(reg) {
    // Register background sync for pending sales
    function registerSync() {
      if (!navigator.onLine) return;
      reg.sync.register('sync-pending-sales').catch(function(err) {
        console.log('[PWA] Background sync registration not supported:', err.message);
      });
    }

    // Re-register after each sync cycle
    document.addEventListener('sync-complete', registerSync);

    // Register on pending sales count change
    window.addEventListener('pending-sync-updated', function(e) {
      if (e.detail && e.detail.count > 0 && navigator.onLine) {
        reg.sync.register('sync-pending-sales').catch(function() {});
      }
    });

    // Periodic sync (if supported)
    if ('periodicSync' in reg) {
      reg.periodicSync.register('sync-refresh-master', {
        minInterval: 60 * 60 * 1000 // 1 hour
      }).catch(function(err) {
        console.log('[PWA] Periodic sync not supported:', err.message);
      });
    }
  });
})();

// ─── Push Notification ───────────────────────────────────────
(function() {
  if (!('Notification' in window) || !('serviceWorker' in navigator)) return;

  // Request permission on first interaction
  function requestNotificationPermission() {
    if (Notification.permission === 'granted') return;
    if (Notification.permission === 'denied') return;

    Notification.requestPermission().then(function(permission) {
      if (permission === 'granted') {
        console.log('[PWA] Notification permission granted');
        registerFCMToken();
      }
    });
  }

  function registerFCMToken() {
    // FCM registration handled by existing backend route
    // /api/register-fcm-token — called by backend when needed
  }

  // Delayed permission request (after page loaded)
  setTimeout(requestNotificationPermission, 10000); // 10s delay — non-intrusive

  // Also on first user click
  document.addEventListener('click', function() {
    if (Notification.permission === 'default') {
      requestNotificationPermission();
    }
  }, { once: true });
})();

// ─── Badging API ─────────────────────────────────────────────
(function() {
  if (!navigator.setAppBadge) return;

  function updateBadge() {
    var sm = window.syncManager;
    if (!sm) return;

    sm.getTotalPending().then(function(count) {
      if (count > 0) {
        navigator.setAppBadge(count).catch(function() {});
      } else {
        navigator.clearAppBadge().catch(function() {});
      }
    });
  }

  window.addEventListener('pending-sync-updated', updateBadge);
  window.addEventListener('sync-complete', function() {
    setTimeout(updateBadge, 1000); // wait for state to settle
  });

  // Initial update after managers init
  setTimeout(updateBadge, 5000);
})();

/**
 * Retrieve printer settings from localStorage.
 */
window.getPrinter = function() {
  if (localStorage.printer == undefined) {
    console.error('printer didn\'t set');
    return Error('printer didn\'t set');
  }

  return JSON.parse(localStorage.printer);
}

/**
 * Print text to a USB ESC/POS printer.
 * 
 * @param {string} text - The ESC/POS command string to print.
 * @returns {Promise<void>}
 */
window.printToUSBPrinter = async function(text) {
  let receiptText = text;
  console.log(receiptText);

  try {
    if (localStorage.printer == undefined) {
      console.error('No USB printer selected');
      return;
    }

    let printer = JSON.parse(localStorage.printer);
    const devices = await navigator.usb.getDevices();

    const device = devices.find(device => device.vendorId === printer.vendorId);
    if (device) {
      console.log('Found USB device:', device.productName);

      await device.open();
      await device.selectConfiguration(1);
      await device.claimInterface(0);

      const encoder = new TextEncoder();
      const data = encoder.encode(receiptText);
      const endpoint = device.configuration.interfaces[0].alternate.endpoints.filter(endpoint => endpoint.direction === 'out')[0]
      await device.transferOut(endpoint.endpointNumber, data);

      console.log('Data sent to printer');
    } else {
      console.log('No USB device with the specified vendor ID found');
      new FilamentNotification()
        .title('You should choose the printer first in printer setting')
        .danger()
        .actions([
          new FilamentNotificationAction('Setting')
            .icon('heroicon-o-cog-6-tooth')
            .button()
            .url('/member/printer'),
        ])
        .send()
    }
  } catch (e) {
    console.error(e);
  }
}

/**
 * Pad text for receipt printing.
 * 
 * @param {string} text - The text to pad.
 * @param {number} length - Target length.
 * @param {boolean} alignRight - Whether to align right.
 * @param {boolean} center - Whether to center the text.
 * @param {string} textSize - Text size ('normal' or 'large').
 * @returns {string} The padded text.
 */
window.padText = function(text, length, alignRight = false, center = false, textSize = 'normal') {
  const sizes = {
    'normal': '\x1D\x21\x00', // Normal text
    'large': '\x1D\x21\x11', // Large text
  }[textSize];
  let paddedText = text;

  if (center) {
    const padLength = Math.max(0, length - text.length);
    const padStart = Math.floor(padLength / 2);
    const padEnd = Math.ceil(padLength / 2);
    paddedText = ' '.repeat(padStart) + text + ' '.repeat(padEnd);
  } else if (alignRight) {
    paddedText = text.padStart(length);
  } else {
    paddedText = text.padEnd(length);
  }

  return paddedText;
}

/**
 * Formats a number as currency.
 * For IDR, decimals are hidden for a cleaner POS look.
 * 
 * @param {number} number - The value to format.
 * @param {string|null} currency - Currency code (e.g., 'IDR', 'USD').
 * @returns {string} The formatted currency string.
 */
window.moneyFormat = function(number, currency = null) {
  const activeCurrency = currency || window.zonakasirCurrency || 'IDR';
  const activeLocale = window.zonakasirLocale || 'en';

  const options = {
    style: 'currency',
    currency: activeCurrency,
  };

  if (activeCurrency === 'IDR') {
    options.minimumFractionDigits = 0;
  }

  const formatter = new Intl.NumberFormat(activeLocale, options);

  return formatter.format(number);
}

/**
 * Formats a number using the active locale.
 * 
 * @param {number} number - The value to format.
 * @returns {string} The formatted number string.
 */
window.numberFormat = function(number) {
  const activeLocale = window.lakasirLocale || 'en';
  const formatter = new Intl.NumberFormat(activeLocale);

  return formatter.format(number);
}

/**
 * Build receipt preview HTML matching thermal printer output.
 */
window.buildReceiptPreviewHtml = function(selling, about, printerData) {
  const line = '─'.repeat(32);
  let h = '';

  const esc = (s) => s == null ? '' : String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  // Header
  if (about) {
    h += `<div class="text-lg font-bold text-center">${esc(about.shop_name)}</div>`;
    if (about.shop_location) h += `<div class="text-center text-sm">${esc(about.shop_location)}</div>`;
    if (printerData?.header) h += `<div class="text-center text-sm">${esc(printerData.header)}</div>`;
  }
  h += `<div class="text-center tracking-[0.2em] my-1">${line}</div>`;

  // Info
  if (selling.user?.name) h += `<div class="flex justify-between text-sm"><span>Cashier</span><span>${esc(selling.user.name)}</span></div>`;
  if (selling.table?.number) h += `<div class="flex justify-between text-sm"><span>Table</span><span>${esc(selling.table.number)}</span></div>`;
  if (selling.payment_method?.name) h += `<div class="flex justify-between text-sm"><span>Payment</span><span>${esc(selling.payment_method.name)}</span></div>`;
  if (selling.member?.name) h += `<div class="flex justify-between text-sm"><span>Member</span><span>${esc(selling.member.name)}</span></div>`;
  h += `<div class="text-center tracking-[0.2em] my-1">${line}</div>`;

  // Items
  const details = selling.selling_details || selling.details || [];
  details.forEach(d => {
    const qty = d.qty || 1;
    const pricePerUnit = d.price_per_unit || (d.price ? d.price / qty : 0);
    const name = d.product?.name || d.name || 'Product';
    h += `<div class="flex justify-between text-sm"><span>${esc(name)}</span><span>${moneyFormat(pricePerUnit)} x ${qty}</span></div>`;
    if (d.discount_price > 0) {
      h += `<div class="text-right text-sm">(${moneyFormat(d.discount_price)})</div>`;
    }
    h += `<div class="text-right text-sm font-semibold">${moneyFormat(d.price || d.total_price || 0)}</div>`;
  });
  h += `<div class="text-center tracking-[0.2em] my-1">${line}</div>`;

  // Totals
  if (selling.tax > 0) {
    h += `<div class="flex justify-between text-sm"><span>Tax</span><span>${selling.tax}%</span></div>`;
    h += `<div class="flex justify-between text-sm"><span>Tax price</span><span>${moneyFormat(selling.tax_price)}</span></div>`;
  }
  h += `<div class="flex justify-between text-sm"><span>Subtotal</span><span>${moneyFormat(selling.total_price)}</span></div>`;
  const discount = (selling.total_discount_per_item || 0) + (selling.discount_price || 0);
  if (discount > 0) {
    h += `<div class="flex justify-between text-sm"><span>Discount</span><span>(${moneyFormat(discount)})</span></div>`;
  }
  h += `<div class="flex justify-between text-sm font-bold"><span>Total price</span><span>${moneyFormat(selling.grand_total_price)}</span></div>`;
  h += `<div class="text-center tracking-[0.2em] my-1">${line}</div>`;
  h += `<div class="flex justify-between text-sm"><span>Payed money</span><span>${moneyFormat(selling.payed_money)}</span></div>`;
  h += `<div class="flex justify-between text-sm"><span>Change</span><span>${moneyFormat(selling.money_changes)}</span></div>`;

  // Footer
  if (printerData?.footer) {
    h += `<div class="text-center text-sm mt-1">${esc(printerData.footer)}</div>`;
  }
  h += `<div class="text-left text-xs mt-1">copy</div>`;

  return h;
}

// ─── Logout: Clear SW session cache for account switching ────
document.addEventListener('DOMContentLoaded', function() {
  document.addEventListener('submit', function(e) {
    var form = e.target;
    if (form.method && form.method.toUpperCase() === 'POST') {
      var action = form.action || window.location.href;
      if (action.includes('/logout')) {
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
          navigator.serviceWorker.controller.postMessage({ type: 'CLEAR_SESSION' });
        }
        // Also clear IndexedDB session data
        if (window.offlineManager && window.offlineManager.db) {
          window.offlineManager.clearAll().catch(function() {});
        }
      }
    }
  });
});

