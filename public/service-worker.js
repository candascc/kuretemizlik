// Service Worker for Küre Temizlik PWA
// Version: 2.0.0

const APP_VERSION = 'v2';
const SCOPE_URL = self.registration && self.registration.scope ? new URL(self.registration.scope) : null;
const BASE_PATH = SCOPE_URL ? SCOPE_URL.pathname.replace(/\/$/, '') : '';
const ORIGIN = self.location.origin;
const SCOPE_ORIGIN = ORIGIN + BASE_PATH;

const resolvePath = (path) => {
  const normalized = path.startsWith('/') ? path : `/${path}`;
  return (`${BASE_PATH}${normalized}`).replace(/\/+/g, '/');
};

const CACHE_NAME = `kure-temizlik-${APP_VERSION}`;
const OFFLINE_URL = resolvePath('/offline');
const STATIC_ASSETS = [
  resolvePath('/'),
  resolvePath('/offline'),
  resolvePath('/manifest.json'),
  resolvePath('/assets/dist/app.bundle.css'),
  resolvePath('/assets/dist/app.bundle.js'),
  resolvePath('/assets/css/custom.css'),
  resolvePath('/assets/img/logokureapp.png')
];

const PRECACHE_ON_INSTALL = [...new Set(STATIC_ASSETS)];

// Install event - cache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE_ON_INSTALL))
      .then(() => self.skipWaiting())
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    }).then(() => {
      return self.clients.claim(); // Take control of all pages
    })
  );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') {
    return;
  }

  // Skip cross-origin requests
  if (!event.request.url.startsWith(SCOPE_ORIGIN)) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      // Return cached version if available
      if (cachedResponse) {
        return cachedResponse;
      }

      // Otherwise fetch from network
      return fetch(event.request).then((response) => {
        // Don't cache if not successful
        if (!response || response.status !== 200 || response.type !== 'basic') {
          return response;
        }

        // Clone response for caching
        const responseToCache = response.clone();

        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache).catch(() => {});
        }).catch(() => {});

        return response;
      }).catch(() => {
        // Network failed - return offline page if request is navigation
        if (event.request.mode === 'navigate') {
          return caches.match(OFFLINE_URL);
        }
        // For other resources, return cached version or fail gracefully
        return caches.match(event.request);
      });
    })
  );
});

// Background sync - for offline actions
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-jobs') {
    event.waitUntil(syncJobs());
  }
  if (event.tag === 'sync-payments') {
    event.waitUntil(syncPayments());
  }
});

// Sync jobs function
async function syncJobs() {
  // Get pending jobs from IndexedDB
  // Send them to server
  // Clear from IndexedDB on success
}

// Sync payments function
async function syncPayments() {
  // Get pending payments from IndexedDB
  // Send them to server
  // Clear from IndexedDB on success
}

// Push notification event
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'Küre Temizlik';
  const options = {
    body: data.body || 'Yeni bildirim',
    icon: resolvePath('/assets/icon-192.png'),
    badge: resolvePath('/assets/badge-72.png'),
    vibrate: [200, 100, 200],
    data: data,
    actions: data.actions || []
  };

  event.waitUntil(
    self.registration.showNotification(title, options)
  );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      // If app is already open, focus it
      for (let client of clientList) {
        if (client.url === SCOPE_ORIGIN && 'focus' in client) {
          return client.focus();
        }
      }
      // Otherwise open new window
      if (clients.openWindow) {
        const target = event.notification.data?.url ? resolvePath(event.notification.data.url) : resolvePath('/');
        return clients.openWindow(target);
      }
    })
  );
});

self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

