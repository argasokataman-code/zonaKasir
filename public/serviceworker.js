const CACHE_VERSION = 'a059adea';
const STATIC_CACHE = `zonakasir-static-${CACHE_VERSION}`;
const PAGES_CACHE = `zonakasir-pages-${CACHE_VERSION}`;
const API_CACHE = `zonakasir-api-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `zonakasir-dynamic-${CACHE_VERSION}`;
const OFFLINE_PAGE = '/offline';

const APP_SHELL_URLS = [
  '/', '/offline', '/member/offline-pos', '/favicon.ico',
  '/images/icons/icon-48x48.png', '/images/icons/icon-72x72.png',
  '/images/icons/icon-96x96.png', '/images/icons/icon-128x128.png',
  '/images/icons/icon-144x144.png', '/images/icons/icon-152x152.png',
  '/images/icons/icon-192x192.png', '/images/icons/icon-512x512.png',
];

const API_ROUTES_TO_CACHE = [
  '/api/master/product', '/api/master/category', '/api/master/member',
  '/api/master/payment-method', '/api/about',
];

// ─── Route Matchers ──────────────────────────────────────────

function isApiRoute(url) {
  try { return new URL(url, self.location.origin).pathname.startsWith('/api/'); }
  catch { return false; }
}

function isMasterDataApi(url) {
  try {
    const path = new URL(url, self.location.origin).pathname;
    return API_ROUTES_TO_CACHE.some(r => path.startsWith(r));
  } catch { return false; }
}

function isStaticAsset(url) {
  try {
    const path = new URL(url, self.location.origin).pathname;
    return /^\/(build|js|css|images|assets)\//.test(path) || /\.(css|js|woff2?|ttf|svg|png|ico)$/.test(path);
  } catch { return false; }
}

function isNavigationRequest(event) {
  return event.request.mode === 'navigate' ||
    (event.request.method === 'GET' && event.request.headers.get('accept')?.includes('text/html'));
}

function isLivewireUpdate(event) {
  return event.request.method === 'POST' && event.request.url.includes('/livewire/update');
}

// ─── Cache Helpers ───────────────────────────────────────────

async function trimCache(cacheName, max) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  if (keys.length > max) {
    await Promise.all(keys.slice(0, keys.length - max).map(k => cache.delete(k)));
  }
}

// ─── Install / Activate ──────────────────────────────────────

async function cacheStaticAssets() {
  const cache = await caches.open(STATIC_CACHE);
  await Promise.all(APP_SHELL_URLS.map(u => cache.add(u).catch(() => {})));

  // Vite manifest assets
  try {
    const m = await (await fetch('/build/manifest.json')).json();
    await Promise.all(Object.values(m).map(e => cache.add(`/build/${e.file}`).catch(() => {})));
  } catch {}

  // Custom JS
  const js = ['custom-javascript', 'printer', 'indexeddb', 'offline-manager', 'sync-manager', 'offline-indicator', 'session-timeout', 'html5-qrcode'];
  await Promise.all(js.map(n => cache.add(`/js/app/${n}.js`).catch(() => {})));

  // Filament assets
  const fJs = ['/js/filament/filament/app.js', '/js/filament/support/support.js', '/js/filament/notifications/notifications.js'];
  const fCss = ['/css/filament/filament/app.css', '/css/filament/support/support.css'];
  await Promise.all([...fJs, ...fCss].map(u => cache.add(u).catch(() => {})));
}

// ─── Fetch Handlers ──────────────────────────────────────────

async function handleApiRequest(event) {
  const isGet = event.request.method === 'GET';
  if (!isGet) {
    try { return await fetch(event.request); }
    catch { return new Response(JSON.stringify({ error: 'offline' }), { status: 503, headers: { 'Content-Type': 'application/json' } }); }
  }

  try {
    const ctrl = new AbortController();
    const tid = setTimeout(() => ctrl.abort(), 8000);
    const resp = await fetch(event.request, { signal: ctrl.signal });
    clearTimeout(tid);
    if (resp.ok && isMasterDataApi(event.request.url)) {
      const clone = resp.clone();
      (await caches.open(API_CACHE)).put(event.request, clone);
    }
    return resp;
  } catch {
    if (isMasterDataApi(event.request.url)) {
      const cached = await caches.match(event.request);
      if (cached) return cached;
    }
    return new Response(JSON.stringify({ error: 'offline' }), { status: 503, headers: { 'Content-Type': 'application/json' } });
  }
}

async function handlePageRequest(event) {
  // Livewire POST: passthrough — bypass SW entirely
  if (isLivewireUpdate(event)) {
    return fetch(event.request).catch(() =>
      new Response(JSON.stringify({ message: 'Offline', errors: { server: ['No network'] } }), {
        status: 419, headers: { 'Content-Type': 'application/json' },
      })
    );
  }

  // Stale-while-revalidate for navigation:
  // 1. Serve from cache instantly
  // 2. Fetch from network in background → update cache
  // 3. If no cache → network-first
  const cached = await caches.match(event.request);
  if (cached) {
    // Background revalidate — use waitUntil to prevent premature SW termination
    event.waitUntil(
      fetch(event.request)
        .then(resp => {
          if (resp.ok) {
            return caches.open(PAGES_CACHE).then(cache => {
              cache.put(event.request, resp.clone());
              trimCache(PAGES_CACHE, 50);
            });
          }
        })
        .catch(() => {})
    );
    return cached;
  }

  // No cache → network-first with timeout
  try {
    const ctrl = new AbortController();
    const tid = setTimeout(() => ctrl.abort(), 5000);
    const resp = await fetch(event.request, { signal: ctrl.signal });
    clearTimeout(tid);
    if (resp.ok) {
      const cache = await caches.open(PAGES_CACHE);
      cache.put(event.request, resp.clone());
      trimCache(PAGES_CACHE, 50);
    }
    return resp;
  } catch {
    return caches.match(OFFLINE_PAGE);
  }
}

async function handleStaticRequest(event) {
  const url = new URL(event.request.url);
  if (!url.protocol.startsWith('http')) return fetch(event.request);
  const cached = await caches.match(event.request);
  if (cached) return cached;
  try {
    const resp = await fetch(event.request);
    if (resp.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(event.request, resp.clone());
      trimCache(STATIC_CACHE, 80);
    }
    return resp;
  } catch { return new Response('', { status: 408, statusText: 'Offline' }); }
}

async function handleDynamicRequest(event) {
  const url = new URL(event.request.url);
  if (!url.protocol.startsWith('http')) return fetch(event.request);
  const cached = await caches.match(event.request);
  if (cached) return cached;
  try {
    const resp = await fetch(event.request);
    if (resp.ok && event.request.method === 'GET') {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(event.request, resp.clone());
      trimCache(DYNAMIC_CACHE, 100);
    }
    return resp;
  } catch { return new Response('', { status: 408, statusText: 'Offline' }); }
}

// ─── Sync Pending Sales (from IndexedDB) ─────────────────────

async function syncPendingSales(event) {
  try {
    // SW can't access IndexedDB "zonakasir_offline" directly (different DB scope).
    // Instead, message all clients to trigger sync.
    const clients = await self.clients.matchAll();
    if (clients.length > 0) {
      clients.forEach(c => c.postMessage({ type: 'BACKGROUND_SYNC' }));
    } else {
      // No open clients — try direct API call for each pending sale.
      // This requires storing pending sales in Cache API as fallback.
      // For now, rely on SyncManager polling when client reopens.
      console.log('[SW] Background sync: no active clients, waiting for next poll');
    }
  } catch (err) {
    console.error('[SW] Background sync error:', err);
  }
}

// ─── Push Notification ──────────────────────────────────────

async function handlePushEvent(event) {
  let data = { title: 'zonaKasir', body: '', icon: '/images/icons/icon-192x192.png', badge: '/images/icons/icon-72x72.png', tag: 'zonakasir-notification', data: {} };

  if (event.data) {
    try {
      const payload = event.data.json();
      data = { ...data, ...payload };
    } catch {
      data.body = event.data.text() || data.body;
    }
  }

  event.waitUntil(
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: data.icon,
      badge: data.badge,
      tag: data.tag || 'zonakasir-notification',
      data: data.data || {},
      vibrate: [200, 100, 200],
      actions: data.actions || [
        { action: 'open', title: 'Open App' },
        { action: 'sync', title: 'Sync Now' },
      ],
    })
  );
}

async function handleNotificationClick(event) {
  event.notification.close();

  const action = event.action;
  const urlToOpen = new URL('/member', self.location.origin).href;

  if (action === 'sync') {
    // Notify clients to sync
    const clients = await self.clients.matchAll();
    clients.forEach(c => c.postMessage({ type: 'BACKGROUND_SYNC' }));
  }

  // Focus or open app window
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(windowClients => {
      for (const client of windowClients) {
        if (client.url.includes(self.location.origin) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) return clients.openWindow(urlToOpen);
    })
  );
}

// ─── Event Listeners ─────────────────────────────────────────

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(cacheStaticAssets());
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k.startsWith('zonakasir-') && !k.includes(CACHE_VERSION)).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// Fetch
self.addEventListener('fetch', event => {
  const url = event.request.url;
  // Never cache auth-related pages — allows account switching
  if (url.includes('/login') || url.includes('/logout') || url.includes('/auth/google') || url.includes('/member/offline')) return;

  if (isApiRoute(url)) event.respondWith(handleApiRequest(event));
  else if (isNavigationRequest(event)) event.respondWith(handlePageRequest(event));
  else if (isStaticAsset(url)) event.respondWith(handleStaticRequest(event));
  else event.respondWith(handleDynamicRequest(event));
});

// Background Sync — fired by OS even when tab closed (if registered)
self.addEventListener('sync', event => {
  if (event.tag === 'sync-pending-sales') {
    event.waitUntil(syncPendingSales(event));
  }
  if (event.tag === 'sync-master-data') {
    event.waitUntil(syncPendingSales(event)); // same logic — msg clients
  }
});

// Periodic Background Sync — Chrome 80+, fired periodically by browser
self.addEventListener('periodicsync', event => {
  if (event.tag === 'sync-data') {
    event.waitUntil(syncPendingSales(event));
  }
  if (event.tag === 'sync-refresh-master') {
    event.waitUntil(syncPendingSales(event));
  }
});

// Push Notification
self.addEventListener('push', handlePushEvent);
self.addEventListener('notificationclick', handleNotificationClick);

// Message from clients
self.addEventListener('message', event => {
  const d = event.data;
  if (!d || !d.type) return;

  if (d.type === 'SKIP_WAITING') self.skipWaiting();
  if (d.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(STATIC_CACHE).then(cache =>
        Promise.all(d.urls.map(u => cache.add(u).catch(() => {})))
      )
    );
  }
  if (d.type === 'CLEAR_PAGES_CACHE') event.waitUntil(caches.delete(PAGES_CACHE));
  if (d.type === 'CLEAR_SESSION') {
    // Clear all page + API caches on logout for clean account switch
    event.waitUntil(
      Promise.all([
        caches.delete(PAGES_CACHE),
        caches.delete(API_CACHE),
        caches.delete(DYNAMIC_CACHE),
      ])
    );
  }
});
