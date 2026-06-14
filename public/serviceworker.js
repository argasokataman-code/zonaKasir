const CACHE_VERSION = 'v2';
const STATIC_CACHE = `zonakasir-static-${CACHE_VERSION}`;
const PAGES_CACHE = `zonakasir-pages-${CACHE_VERSION}`;
const API_CACHE = `zonakasir-api-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `zonakasir-dynamic-${CACHE_VERSION}`;

const APP_SHELL_URLS = [
  '/',
  '/offline',
  '/member/offline-pos',
  '/favicon.ico',
  '/images/icons/icon-48x48.png',
  '/images/icons/icon-72x72.png',
  '/images/icons/icon-96x96.png',
  '/images/icons/icon-128x128.png',
  '/images/icons/icon-144x144.png',
  '/images/icons/icon-152x152.png',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png',
];

const API_ROUTES_TO_CACHE = [
  '/api/master/product',
  '/api/master/category',
  '/api/master/member',
  '/api/master/payment-method',
  '/api/about',
];

function isApiRoute(url) {
  try {
    const parsed = new URL(url, self.location.origin);
    return parsed.pathname.startsWith('/api/');
  } catch {
    return false;
  }
}

function isMasterDataApi(url) {
  try {
    const parsed = new URL(url, self.location.origin);
    return API_ROUTES_TO_CACHE.some(route => parsed.pathname.startsWith(route));
  } catch {
    return false;
  }
}

function isStaticAsset(url) {
  try {
    const parsed = new URL(url, self.location.origin);
    const path = parsed.pathname;
    return (
      path.startsWith('/build/') ||
      path.startsWith('/js/') ||
      path.startsWith('/css/') ||
      path.startsWith('/images/') ||
      path.startsWith('/assets/') ||
      path.endsWith('.css') ||
      path.endsWith('.js') ||
      path.endsWith('.woff') ||
      path.endsWith('.woff2') ||
      path.endsWith('.ttf') ||
      path.endsWith('.svg') ||
      path.endsWith('.png') ||
      path.endsWith('.ico')
    );
  } catch {
    return false;
  }
}

function isNavigationRequest(event) {
  return event.request.mode === 'navigate' ||
    (event.request.method === 'GET' &&
      event.request.headers.get('accept')?.includes('text/html'));
}

function isLivewireUpdate(event) {
  return event.request.method === 'POST' &&
    event.request.url.includes('/livewire/update');
}

async function cacheStaticAssets() {
  const cache = await caches.open(STATIC_CACHE);

  await Promise.all(
    APP_SHELL_URLS.map(url =>
      cache.add(url).catch(err => console.warn(`[SW] Failed to cache ${url}:`, err))
    )
  );

  try {
    const resp = await fetch('/build/manifest.json');
    if (resp.ok) {
      const manifest = await resp.json();
      const assetUrls = Object.values(manifest)
        .map(entry => `/build/${entry.file}`)
        .filter(url => !url.endsWith('.map'));

      await Promise.all(
        assetUrls.map(url =>
          cache.add(url).catch(err => console.warn(`[SW] Failed to cache asset ${url}:`, err))
        )
      );
    }
  } catch (err) {
    console.warn('[SW] Failed to load manifest:', err);
  }

  try {
    const jsDirResp = await fetch('/js/app/');
    if (jsDirResp.ok) {
      const jsFiles = ['/js/app/custom-javascript.js', '/js/app/printer.js', '/js/app/indexeddb.js', '/js/app/offline-manager.js', '/js/app/sync-manager.js', '/js/app/offline-indicator.js', '/js/app/session-timeout.js'];
      await Promise.all(
        jsFiles.map(url =>
          cache.add(url).catch(err => console.warn(`[SW] Failed to cache JS ${url}:`, err))
        )
      );
    }
  } catch (err) {
    console.warn('[SW] Failed to cache JS files:', err);
  }

  try {
    const filamentJs = [
      '/js/filament/filament/app.js',
      '/js/filament/support/support.js',
      '/js/filament/notifications/notifications.js',
    ];
    const filamentCss = [
      '/css/filament/filament/app.css',
      '/css/filament/support/support.css',
    ];
    await Promise.all(
      [...filamentJs, ...filamentCss].map(url =>
        cache.add(url).catch(err => console.warn(`[SW] Failed to cache Filament ${url}:`, err))
      )
    );
  } catch (err) {
    console.warn('[SW] Failed to cache Filament assets:', err);
  }
}

async function trimCache(cacheName, maxItems) {
  const cache = await caches.open(cacheName);
  const keys = await cache.keys();
  if (keys.length > maxItems) {
    await Promise.all(
      keys.slice(0, keys.length - maxItems).map(key => cache.delete(key))
    );
  }
}

async function handleApiRequest(event) {
  const url = event.request.url;
  const networkFirst = event.request.method === 'GET';

  if (!networkFirst) {
    try {
      const response = await fetch(event.request);
      return response;
    } catch {
      return new Response(JSON.stringify({ error: 'offline', message: 'No network connection' }), {
        status: 503,
        headers: { 'Content-Type': 'application/json' },
      });
    }
  }

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 8000);

    const response = await fetch(event.request, { signal: controller.signal });
    clearTimeout(timeoutId);

    if (response.ok && isMasterDataApi(url)) {
      const cloned = response.clone();
      const cache = await caches.open(API_CACHE);
      cache.put(event.request, cloned);
    }

    return response;
  } catch {
    if (isMasterDataApi(url)) {
      const cached = await caches.match(event.request);
      if (cached) return cached;
    }

    return new Response(JSON.stringify({ error: 'offline', message: 'No network connection' }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' },
    });
  }
}

async function handlePageRequest(event) {
  const url = event.request.url;

  if (isLivewireUpdate(event)) {
    try {
      const response = await fetch(event.request);
      return response;
    } catch {
      // Return minimal Livewire-compatible error response
      return new Response(JSON.stringify({
        message: 'Offline',
        errors: { server: ['No network connection. Changes not saved.'] },
      }), {
        status: 419, // Session expired status — Livewire handles this gracefully
        headers: { 'Content-Type': 'application/json' },
      });
    }
  }

  try {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000);

    const response = await fetch(event.request, { signal: controller.signal });
    clearTimeout(timeoutId);

    if (response.ok) {
      const cache = await caches.open(PAGES_CACHE);
      cache.put(event.request, response.clone());
      trimCache(PAGES_CACHE, 50);
    }

    return response;
  } catch {
    const cached = await caches.match(event.request);
    if (cached) return cached;

    return caches.match('/offline');
  }
}

async function handleStaticRequest(event) {
  const cached = await caches.match(event.request);
  if (cached) return cached;

  try {
    const response = await fetch(event.request);
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(event.request, response.clone());
    }
    return response;
  } catch {
    return new Response('', { status: 408, statusText: 'Offline' });
  }
}

async function handleDynamicRequest(event) {
  const cached = await caches.match(event.request);
  if (cached) return cached;

  try {
    const response = await fetch(event.request);
    if (response.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(event.request, response.clone());
      trimCache(DYNAMIC_CACHE, 100);
    }
    return response;
  } catch {
    return new Response('', { status: 408, statusText: 'Offline' });
  }
}

self.addEventListener('install', event => {
  self.skipWaiting();
  event.waitUntil(cacheStaticAssets());
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => {
      return Promise.all(
        keys
          .filter(key => key.startsWith('zonakasir-'))
          .filter(key => !key.includes(CACHE_VERSION))
          .map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  const url = event.request.url;

  if (url.includes('/member/login') || url.includes('/admin/login')) {
    return;
  }

  if (isApiRoute(url)) {
    event.respondWith(handleApiRequest(event));
  } else if (isNavigationRequest(event)) {
    event.respondWith(handlePageRequest(event));
  } else if (isStaticAsset(url)) {
    event.respondWith(handleStaticRequest(event));
  } else {
    event.respondWith(handleDynamicRequest(event));
  }
});

self.addEventListener('periodicsync', event => {
  if (event.tag === 'sync-data') {
    event.waitUntil(syncMasterData());
  }
});

async function syncMasterData() {
  const clients = await self.clients.matchAll();
  clients.forEach(client => {
    client.postMessage({ type: 'SYNC_MASTER_DATA' });
  });
}

self.addEventListener('message', event => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }

  if (event.data && event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(STATIC_CACHE).then(cache => {
        return Promise.all(
          event.data.urls.map(url =>
            cache.add(url).catch(err => console.warn(`[SW] Failed to cache ${url}:`, err))
          )
        );
      })
    );
  }

  if (event.data && event.data.type === 'CLEAR_PAGES_CACHE') {
    event.waitUntil(caches.delete(PAGES_CACHE));
  }
});
