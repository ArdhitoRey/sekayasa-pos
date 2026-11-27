const CACHE_NAME = 'tandha-kasir-v5';
const urlsToCache = [
  '/pos?app=kasir',
  '/manifest.json?v=4',
  '/tandha-icon-192.png?v=4',
  '/tandha-icon-512.png?v=4'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(
      keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
    )).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', event => {
  // Network first so code updates apply automatically whenever connected to Wi-Fi/internet
  if (event.request.method === 'GET' && event.request.url.includes('/pos')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
  }
});
