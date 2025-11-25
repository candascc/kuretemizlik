/* 
 * Service Worker - Stub Mode (ROUND 15)
 * 
 * This application does not actively use Service Workers for offline/PWA features.
 * This stub exists to prevent errors if a browser tries to load it, but it does nothing.
 * All SW registrations are disabled in production (see global-footer.php).
 */

// Minimal install handler - no precache, no errors
self.addEventListener('install', (event) => {
  // Skip waiting immediately - no active SW needed
  self.skipWaiting();
  event.waitUntil(Promise.resolve());
});

// Minimal activate handler - clean up old caches silently
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.map((key) => caches.delete(key).catch(() => {}))
      ))
      .then(() => self.clients.claim())
      .catch(() => {}) // Silent failure
  );
});

// Fetch handler - pass through everything (no caching)
self.addEventListener('fetch', (event) => {
  // Do nothing - let browser handle all requests normally
  // No interception, no caching, no errors
});

// All other handlers (sync, push, message, notificationclick) are intentionally omitted
// They won't fire if SW is not registered, and if it is, they do nothing.
