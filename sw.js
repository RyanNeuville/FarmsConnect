const CACHE_NAME = "farms-connect-v1";
const ASSETS = [
  "./",
  "./index.html",
  "./css/app.css",
  "./js/app.js",
  "./manifest.json",
  "./assets/icon.png",
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
        return response || caches.match("./index.html");
      });
    }),
  );
});
