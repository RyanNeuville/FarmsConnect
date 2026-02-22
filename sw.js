const CACHE_NAME = "farms-connect-v1";
const ASSETS = [
  "./",
  "./index.html",
  "./css/app.css",
  "./js/app.js",
  "./manifest.json",
  "./assets/icon.svg",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(ASSETS)),
  );
  self.skipWaiting();
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    fetch(event.request).catch((error) => {
      return caches.match(event.request).then((response) => {
        // If offline and request is not in cache, fallback to index.html for PWA routing
        return response || caches.match("./index.html");
      });
    }),
  );
});
