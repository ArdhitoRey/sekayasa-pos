const CACHE_NAME = 'tandha-kasir-v6';
const urlsToCache = [
  '/pos?app=kasir',
  '/manifest.json?v=5',
  '/assets/Logo%20Tandha.svg?v=5',
  '/tandha-icon-192.png?v=5',
  '/tandha-icon-512.png?v=5',
  '/tandha-maskable-192.png?v=5',
  '/tandha-maskable-512.png?v=5'
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
  if (event.request.method !== 'GET') return;
  const reqUrl = new URL(event.request.url);
  if (reqUrl.origin === self.location.origin && (reqUrl.pathname === '/pos' || reqUrl.pathname === '/manifest.json')) {
    event.respondWith(
      fetch(event.request).catch(() => caches.match(event.request))
    );
  }
});
