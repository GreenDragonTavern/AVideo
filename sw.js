importScripts('workbox-v6.5.3/workbox-sw.js');

workbox.setConfig({
    modulePathPrefix: 'workbox-v6.5.3/',
    debug: false
});

const webSiteRootURL = this.location.href.split('sw.js?')[0];
const FALLBACK_HTML_URL = webSiteRootURL + 'offline';
const CACHE_NAME = 'avideo-cache-ver-3.6';

const staticAssetsCacheName = CACHE_NAME + '-static-assets';

console.log('sw strategy CACHE_NAME', CACHE_NAME);

self.addEventListener('install', (event) => {
    console.log('Service worker installed');
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            const cachedResponse = await cache.match(FALLBACK_HTML_URL);
            if (!cachedResponse) {
                await cache.add(FALLBACK_HTML_URL);
            }
            //console.log('Service worker FALLBACK_HTML_URL added', FALLBACK_HTML_URL);
            // Add other static assets to precache here
        })
    );
});

self.addEventListener('activate', (event) => {
    console.log('Service worker activated');
});
/*
self.addEventListener('fetch', (event) => {
    if (event.request.mode === 'navigate') {
        console.log(`Service worker intercepted request: ${event.request.url}`);
    }
    console.log('fetch', event.request);
    event.respondWith(
        caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            console.log(`Cache hit: ${event.request.url}`);
            return cachedResponse;
          }
          console.log(`Cache miss: ${event.request.url}`);
          return fetch(event.request);
        })
      );
});
*/
workbox.routing.registerRoute(
    ({ request }) => {
        return (request.destination === 'script' ||
            request.destination === 'style' ||
            request.destination === 'image' ||
            request.url.match(/\.map/) ||
            request.url.match(/\.woff2/));
    },
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: staticAssetsCacheName,
        plugins: [
            new workbox.cacheableResponse.CacheableResponsePlugin({
                statuses: [0, 200]
            })
        ]
    })
);

workbox.routing.setCatchHandler(async ({ event }) => {
    console.log('setCatchHandler called', event.request.url);
    if (event.request.destination === 'document') {
        try {
            const networkResponse = await fetch(event.request);
            console.log('networkResponse', networkResponse);
            if (networkResponse.ok) {
                const cache = await caches.open(CACHE_NAME);
                await cache.put(event.request, networkResponse.clone());
                return networkResponse;
            }
        } catch (error) {
            console.error(error);
        }
        // Redirect to the offline page if the user is offline
        if (navigator.onLine === false) {
            console.log('User is offline, redirecting to offline page');
            return Response.redirect(FALLBACK_HTML_URL);
        }
        // Return the cached response if it exists
        const cachedResponse = await caches.match(FALLBACK_HTML_URL);
        console.log('cachedResponse', cachedResponse);
        if (cachedResponse) {
            return cachedResponse;
        }
    }
    return Response.error();
});
