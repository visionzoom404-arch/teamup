/* سرویس‌ورکر می‌جا — کش استاتیک + صفحه‌ی آفلاین */
const V = 'mija-v2';
const STATIC = ['/css/lux.css', '/css/glass.css', '/js/app.js', '/js/jalali.js', '/img/mija-logo-h.png', '/img/placeholder.svg', '/fonts/Vazirmatn-Regular.woff2', '/fonts/Vazirmatn-Bold.woff2', '/offline'];

self.addEventListener('install', e => { e.waitUntil(caches.open(V).then(c => c.addAll(STATIC)).then(() => self.skipWaiting())); });
self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k !== V).map(k => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', e => {
  const r = e.request, u = new URL(r.url);
  if (r.method !== 'GET' || u.origin !== location.origin) return;
  // صفحات: اول شبکه، در صورت قطعی صفحه‌ی آفلاین
  if (r.mode === 'navigate') {
    e.respondWith(fetch(r).catch(() => caches.match('/offline')));
    return;
  }
  // فایل‌های استاتیک و تصاویر: اول کش، سپس شبکه (stale-while-revalidate)
  if (/\.(css|js|woff2|svg|png|jpg|jpeg|webp)$/.test(u.pathname)) {
    e.respondWith(caches.open(V).then(async c => {
      const hit = await c.match(r);
      const net = fetch(r).then(res => { if (res.ok) c.put(r, res.clone()); return res; }).catch(() => hit);
      return hit || net;
    }));
  }
});
