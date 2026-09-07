/* Mining ERP service worker — app shell offline support.
 * Navigations: network-first, fallback to cached /offline.
 * Static assets (/build, /icons, favicon, fonts): cache-first.
 * Everything else (authenticated HTML/API): network-only, never cached.
 */
const CACHE = 'miningerp-v1';
const OFFLINE_URL = '/offline';
const CORE = [OFFLINE_URL, '/icons/icon-192.png', '/icons/icon-512.png', '/favicon.ico'];
const STATIC_PREFIXES = ['/build/', '/icons/', '/docs-assets/'];
const STATIC_FILES = ['/favicon.ico', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((cache) => cache.addAll(CORE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

function isStatic(url) {
    if (STATIC_FILES.includes(url.pathname)) {
        return true;
    }
    return STATIC_PREFIXES.some((p) => url.pathname.startsWith(p));
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') {
        return;
    }
    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((res) => res)
                .catch(() => caches.match(OFFLINE_URL, { ignoreSearch: true }))
        );
        return;
    }

    if (isStatic(url)) {
        event.respondWith(
            caches.match(request, { ignoreSearch: false }).then((hit) => {
                if (hit) {
                    return hit;
                }
                return fetch(request).then((res) => {
                    if (res && res.ok) {
                        const copy = res.clone();
                        caches.open(CACHE).then((cache) => cache.put(request, copy));
                    }
                    return res;
                });
            })
        );
    }
});
