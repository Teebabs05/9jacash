const CACHE_NAME = '9jacash-cache-v1';
const OFFLINE_ASSETS = [
    '/assets/css/app.css',
    '/assets/js/app.js',
    '/assets/img/favicon.svg',
    '/assets/img/default-avatar.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(OFFLINE_ASSETS)).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

// Cache-first for static assets only; everything else (dynamic pages,
// forms, API calls) always goes to the network so wallet/balance data
// is never served stale from cache.
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    if (event.request.method !== 'GET' || !url.pathname.startsWith('/assets/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});
