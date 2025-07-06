// Service Worker for caching static assets
const CACHE_NAME = 'composer-v11';
const STATIC_CACHE_URLS = [
    '/css/app.min.css?v=11',
    '/js/app.min.js?v=11',
    '/img/github.gif',
    '/img/privatepackagist.png',
    '/',
    '/download/',
    '/doc/'
];

// Install event - cache static assets
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
        .then(function(cache) {
            console.log('Opened cache');
            return cache.addAll(STATIC_CACHE_URLS);
        })
    );
});

// Fetch event - serve from cache first, then network
self.addEventListener('fetch', function(event) {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
        .then(function(response) {
            // Return cached version or fetch from network
            if (response) {
                return response;
            }
            
            return fetch(event.request).then(function(response) {
                // Don't cache non-successful responses
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    return response;
                }

                // Cache CSS, JS, and image files
                if (event.request.url.match(/\.(css|js|png|jpg|gif|svg)$/)) {
                    var responseToCache = response.clone();
                    caches.open(CACHE_NAME)
                    .then(function(cache) {
                        cache.put(event.request, responseToCache);
                    });
                }

                return response;
            });
        })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});