/**
 * push-sw.js — Service Worker لإشعارات الطلبات في لوحة التحكم (Web Push).
 * يعمل في الخلفية: يستقبل الإشعار من الخادم ويعرضه في شريط الإشعارات/شاشة القفل
 * حتى لو كانت لوحة التحكم والمتصفح مغلقين — مثل إشعارات واتساب.
 * يُسجَّل بنطاق /admin/ فقط (admin.js) ولا يتدخل في صفحات المتجر.
 */
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('push', (event) => {
  let data = {};
  try { data = event.data ? event.data.json() : {}; } catch (e) { data = { body: event.data && event.data.text() }; }

  const title = data.title || 'ديوان الأصالة';
  event.waitUntil(self.registration.showNotification(title, {
    body: data.body || 'لديك تنبيه جديد',
    icon: data.icon || '/img/icon-192.png',
    badge: data.badge || '/img/notify-badge.png',
    tag: data.tag || undefined,
    renotify: !!data.tag,
    dir: 'rtl',
    lang: 'ar',
    vibrate: [200, 100, 200, 100, 300],
    requireInteraction: true, // يبقى ظاهرًا على الكمبيوتر حتى يراه المدير
    data: { url: data.url || '/admin/orders' },
  }));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  // نقبل فقط روابط داخل نفس الموقع
  const target = new URL(event.notification.data && event.notification.data.url || '/admin/orders', self.location.origin);
  if (target.origin !== self.location.origin) return;

  event.waitUntil((async () => {
    const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const admin = windows.find((w) => new URL(w.url).pathname.startsWith('/admin'));
    if (admin) {
      await admin.focus();
      return admin.navigate(target.href);
    }
    return self.clients.openWindow(target.href);
  })());
});
