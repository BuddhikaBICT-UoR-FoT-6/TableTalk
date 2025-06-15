const CACHE_NAME = 'tabletalk-v2';
const ASSETS = [
    './',
    './index.html',
    './css/style.css',
    './js/app.js',
    './manifest.json'
];

self.addEventListener('install', (e) => {
    self.skipWaiting(); // Force the waiting service worker to become the active service worker
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS))
    );
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim(); // Claim control immediately
});

self.addEventListener('fetch', (e) => {
    // Only cache static assets, let API calls go to network
    if (e.request.url.includes('/api/')) {
        return;
    }
    
    // Network First strategy for HTML/JS/CSS to ensure updates are fetched
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});
