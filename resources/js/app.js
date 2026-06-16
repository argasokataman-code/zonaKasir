let selectedDevice = null;

// ─── PWA Offline Init ────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  if (window.offlineManager) {
    window.offlineManager.init().then(function() {
      console.log('[PWA] OfflineManager initialized');
      // Defer prefetch to idle — don't block rendering
      var deferPrefetch = window.requestIdleCallback || function(cb) { setTimeout(cb, 3000); };
      deferPrefetch(function() {
        if (window.offlineManager && navigator.onLine) {
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
      });
    }).catch(function(err) {
      console.error('[PWA] OfflineManager init failed:', err);
    });
  }
  // Defer non-critical init — let UI paint first
  var deferInit = window.requestIdleCallback || function(cb) { setTimeout(cb, 1000); };
  deferInit(function() {
    if (window.syncManager) {
      window.syncManager.init();
      console.log('[PWA] SyncManager initialized');
    }
    if (window.offlineIndicator) {
      window.offlineIndicator.init();
      console.log('[PWA] OfflineIndicator initialized');
    }
  });
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

