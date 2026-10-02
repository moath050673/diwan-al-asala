/**
 * admin.js — طبقة مشتركة للوحة التحكم: المصادقة، استدعاء الـ API، الهيكل العام (Sidebar)
 */
const ADMIN_API_BASE = window.DIWAN_API_BASE || '/api';

/**
 * تهريب النصوص قبل إدراجها في innerHTML. ضروري جدًا هنا: بيانات الطلبات (الاسم،
 * العنوان، الملاحظات...) يكتبها أي زائر، وبدون تهريب يمكنه حقن سكربت يسرق رمز المدير.
 */
function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

const AdminAuth = {
  getToken: () => localStorage.getItem('diwan_admin_token'),
  setToken: (t) => localStorage.setItem('diwan_admin_token', t),
  clear: () => localStorage.removeItem('diwan_admin_token'),
  isLoggedIn: function () { return !!this.getToken(); },
};

async function adminRequest(path, options = {}) {
  const res = await fetch(ADMIN_API_BASE + path, {
    ...options,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      Authorization: `Bearer ${AdminAuth.getToken()}`,
      ...(options.headers || {}),
    },
  });
  if (res.status === 401) {
    AdminAuth.clear();
    window.location.href = '/admin';
    return null;
  }
  const json = await res.json().catch(() => ({ success: false, message: 'استجابة غير متوقعة من الخادم' }));
  redirectIfPasswordChangeRequired(res, json);
  return json;
}

/** الخادم يرفض أي عملية حتى تُغيَّر كلمة المرور الأولية — ننقل المدير لنموذج التغيير */
function redirectIfPasswordChangeRequired(res, json) {
  if (res.status === 403 && json && json.code === 'password_change_required' && window.location.pathname !== '/admin/settings') {
    window.location.href = '/admin/settings#password';
  }
}

/** رفع ملفات (FormData) — بدون Content-Type حتى يضيف المتصفح حدود الملف (boundary) */
async function adminUpload(path, formData) {
  const res = await fetch(ADMIN_API_BASE + path, {
    method: 'POST',
    body: formData,
    headers: { Accept: 'application/json', Authorization: `Bearer ${AdminAuth.getToken()}` },
  });
  if (res.status === 401) { AdminAuth.clear(); window.location.href = '/admin'; return null; }
  const json = await res.json().catch(() => ({ success: false, message: 'تعذّر رفع الملف' }));
  redirectIfPasswordChangeRequired(res, json);
  return json;
}

/** أول رسالة خطأ مفهومة من استجابة Laravel */
function apiError(res, fallback) {
  if (res && res.errors) return Object.values(res.errors).flat()[0];
  return (res && res.message) || fallback;
}

function requireAdminAuth() {
  if (!AdminAuth.isLoggedIn()) window.location.href = '/admin';
}

function renderSidebar(active) {
  const el = document.getElementById('admin-sidebar');
  if (!el) return;
  const items = [
    { href: '/admin/dashboard', label: 'لوحة القيادة', key: 'dashboard' },
    { href: '/admin/products', label: 'المنتجات', key: 'products' },
    { href: '/admin/orders', label: 'الطلبات', key: 'orders', badge: true },
    { href: '/admin/customers', label: 'العملاء', key: 'customers' },
    { href: '/admin/categories', label: 'التصنيفات', key: 'categories' },
    { href: '/admin/payments', label: 'الدفعات', key: 'payments' },
    { href: '/admin/settings', label: 'الإعدادات', key: 'settings' },
  ];
  el.innerHTML = `
    <div class="brand">متجر ديوان الأصالة</div>
    <nav>${items.map(i => `<a href="${i.href}" class="${active === i.key ? 'active' : ''}">${i.label}${i.badge ? '<span class="nav-badge" id="orders-badge" hidden></span>' : ''}</a>`).join('')}</nav>
    <div class="side-actions">
      <button class="notify-btn" id="notify-btn" type="button">🔔 تفعيل إشعارات الطلبات</button>
      <button class="logout-btn" id="admin-logout-btn" type="button">تسجيل الخروج</button>
    </div>
  `;
  document.getElementById('admin-logout-btn').addEventListener('click', async () => {
    // إبطال الرمز على الخادم أيضًا — مسحه من المتصفح وحده يتركه صالحًا
    try { await adminRequest('/auth/logout', { method: 'POST' }); } catch (e) { /* نكمل الخروج محليًا */ }
    AdminAuth.clear();
    window.location.href = '/admin';
  });

  OrderAlerts.start();
}

/* ===================================================================
   PushDevice — تسجيل هذا الجهاز لإشعارات الطلبات الحقيقية (Web Push):
   تصل على شاشة القفل/شريط الإشعارات مثل واتساب حتى لو أُغلقت لوحة التحكم.
   Service Worker: /push-sw.js بنطاق /admin/ فقط.
   =================================================================== */
const PushDevice = (() => {
  const SW_URL = '/push-sw.js';
  const SCOPE = '/admin/';

  const supported = () => 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window && window.isSecureContext;
  const isIOS = () => /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  const isStandalone = () => window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;

  // الآيفون يدعم الإشعارات فقط عند فتح اللوحة من أيقونة الشاشة الرئيسية (iOS 16.4+)
  const needsHomeScreen = () => isIOS() && !isStandalone();

  function keyToBytes(base64url) {
    const base64 = (base64url + '='.repeat((4 - base64url.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0));
  }

  function sameKey(subscription, bytes) {
    const current = subscription.options && subscription.options.applicationServerKey;
    if (!current) return true; // متصفحات لا تكشف المفتاح — نفترض أنه نفسه
    const a = new Uint8Array(current);
    return a.length === bytes.length && a.every((v, i) => v === bytes[i]);
  }

  async function registration() {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return null;
    try {
      await navigator.serviceWorker.register(SW_URL, { scope: SCOPE });
      return await navigator.serviceWorker.ready;
    } catch (e) { return null; }
  }

  /** @returns {'ok'|'server'|'unsupported'|'denied'|'error'} */
  async function subscribe() {
    if (!supported() || needsHomeScreen()) return 'unsupported';
    if (Notification.permission !== 'granted') return 'denied';

    const res = await adminRequest('/push/key').catch(() => null);
    const publicKey = res && res.success && res.data.publicKey;
    if (!publicKey) return 'server';

    try {
      const reg = await registration();
      if (!reg) return 'unsupported';
      const key = keyToBytes(publicKey);
      let sub = await reg.pushManager.getSubscription();
      if (sub && !sameKey(sub, key)) { await sub.unsubscribe(); sub = null; } // تغيّرت مفاتيح الخادم
      if (!sub) sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key });

      const encodings = PushManager.supportedContentEncodings || ['aesgcm'];
      const saved = await adminRequest('/push/subscriptions', {
        method: 'POST',
        body: JSON.stringify({ ...sub.toJSON(), contentEncoding: encodings.includes('aes128gcm') ? 'aes128gcm' : 'aesgcm' }),
      });
      return saved && saved.success ? 'ok' : 'error';
    } catch (e) { return 'error'; }
  }

  async function unsubscribe() {
    if (!supported()) return;
    try {
      const reg = await navigator.serviceWorker.getRegistration(SCOPE);
      const sub = reg && await reg.pushManager.getSubscription();
      if (!sub) return;
      await adminRequest('/push/subscriptions/delete', { method: 'POST', body: JSON.stringify({ endpoint: sub.endpoint }) });
      await sub.unsubscribe();
    } catch (e) { /* لا شيء — الاشتراك المنتهي يُحذف تلقائيًا من الخادم عند أول إرسال */ }
  }

  return { registration, subscribe, unsubscribe, needsHomeScreen };
})();

/* ===================================================================
   OrderAlerts — تنبيه فوري بالطلبات الجديدة أثناء فتح لوحة التحكم
   (جوال أو كمبيوتر): صوت + إشعار المتصفح + اهتزاز + رسالة على الشاشة
   + عدد الطلبات الجديدة بجانب "الطلبات" وفي عنوان التبويب.
   للتنبيه واللوحة مغلقة: PushDevice أعلاه (Web Push) أو Telegram (TELEGRAM_* في .env).
   =================================================================== */
const OrderAlerts = (() => {
  const POLL_MS = 15000;
  const SEEN_KEY = 'diwan_admin_last_order_id';
  const ENABLED_KEY = 'diwan_admin_alerts_on';
  const baseTitle = document.title;
  let audioCtx = null;
  let timer = null;

  const isOn = () => localStorage.getItem(ENABLED_KEY) === '1';

  // نغمة قصيرة من ثلاث نوتات (بدون ملف صوتي)
  function chime() {
    if (!audioCtx) return;
    const now = audioCtx.currentTime;
    [880, 1175, 1568].forEach((freq, i) => {
      const osc = audioCtx.createOscillator();
      const gain = audioCtx.createGain();
      osc.type = 'sine';
      osc.frequency.value = freq;
      gain.gain.setValueAtTime(0.0001, now + i * 0.16);
      gain.gain.exponentialRampToValueAtTime(0.35, now + i * 0.16 + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, now + i * 0.16 + 0.45);
      osc.connect(gain).connect(audioCtx.destination);
      osc.start(now + i * 0.16);
      osc.stop(now + i * 0.16 + 0.5);
    });
  }

  /** رسالة على الشاشة — innerHtml يجب أن تكون قيمه الديناميكية مهرّبة مسبقًا بـ escapeHtml */
  function showToast(innerHtml) {
    let stack = document.querySelector('.toast-stack');
    if (!stack) { stack = document.createElement('div'); stack.className = 'toast-stack'; document.body.appendChild(stack); }
    while (stack.children.length >= 4) stack.firstElementChild.remove();
    const el = document.createElement('div');
    el.className = 'admin-toast';
    el.setAttribute('role', 'alert');
    el.innerHTML = innerHtml + '<button class="close" type="button" aria-label="إغلاق">×</button>';
    el.querySelector('.close').addEventListener('click', () => el.remove());
    stack.appendChild(el);
    setTimeout(() => el.remove(), 20000);
  }

  function notice(title, body) {
    showToast(`<div><strong>${escapeHtml(title)}</strong><div>${escapeHtml(body)}</div></div>`);
  }

  function toast(order, isSummary = false) {
    showToast(isSummary ? `
      <div>
        <strong>🔔 ${escapeHtml(order.orderNumber)}</strong>
        <a href="/admin/orders">${escapeHtml(order.customerName)} ←</a>
      </div>` : `
      <div>
        <strong>🔔 طلب جديد #${escapeHtml(order.orderNumber)}</strong>
        <div>${escapeHtml(order.customerName)} — ${formatPrice(order.total)}</div>
        <a href="/admin/orders?open=${encodeURIComponent(order.id)}">عرض الطلب ←</a>
      </div>`);
  }

  async function systemNotification(order) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    const title = `طلب جديد #${order.orderNumber}`;
    // نفس الوسم (tag) الذي يستخدمه إشعار الخادم — إن وصل الاثنان يظهر إشعار واحد فقط
    const options = { body: `${order.customerName} — ${formatPrice(order.total)}`, icon: '/img/icon-192.png', badge: '/img/notify-badge.png', tag: 'order-' + order.id, data: { url: '/admin/orders?open=' + order.id } };
    try {
      // متصفحات الجوال (Android) لا تسمح بـ new Notification — تتطلب Service Worker
      const reg = await PushDevice.registration();
      if (reg) { await reg.showNotification(title, options); return; }
      const n = new Notification(title, options);
      n.onclick = () => { window.focus(); window.location.href = options.data.url; };
    } catch (e) { /* نكتفي بالتنبيه داخل الصفحة */ }
  }

  function setBadge(count) {
    const badge = document.getElementById('orders-badge');
    if (badge) { badge.textContent = count; badge.hidden = !count; }
    document.title = count ? `(${count}) ${baseTitle}` : baseTitle;
  }

  async function poll() {
    const lastSeen = Number(localStorage.getItem(SEEN_KEY) || 0);
    let res;
    try { res = await adminRequest(`/orders/notifications?after_id=${lastSeen}`); } catch (e) { return; }
    if (!res || !res.success) return;
    const { latestId, pendingCount, orders } = res.data;
    setBadge(pendingCount);

    // أول تشغيل على هذا الجهاز: نسجّل آخر طلب فقط بدون تنبيه بالطلبات القديمة
    if (!lastSeen) { localStorage.setItem(SEEN_KEY, latestId); return; }

    if (orders.length) {
      localStorage.setItem(SEEN_KEY, latestId);
      // أحدث 3 طلبات كرسائل منفصلة، والباقي في رسالة واحدة مجمّعة
      const latest = orders.slice(-3);
      latest.forEach(o => { toast(o); systemNotification(o); });
      if (orders.length > latest.length) {
        toast({ id: '', orderNumber: `و${orders.length - latest.length} طلبات أخرى`, customerName: 'افتح صفحة الطلبات', total: 0 }, true);
      }
      chime();
      if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
      document.dispatchEvent(new CustomEvent('admin:new-orders', { detail: orders }));
    } else if (latestId < lastSeen) {
      localStorage.setItem(SEEN_KEY, latestId); // حُذفت طلبات
    }
  }

  function refreshButton() {
    const btn = document.getElementById('notify-btn');
    if (!btn) return;
    btn.classList.toggle('on', isOn());
    btn.textContent = isOn() ? '🔔 الإشعارات مفعّلة' : '🔕 تفعيل إشعارات الطلبات';
  }

  // المتصفحات تمنع الصوت حتى يتفاعل المستخدم مع الصفحة — نفعّله عند أول نقرة
  function unlockAudio() {
    if (!isOn()) return;
    try {
      audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
      if (audioCtx.state === 'suspended') audioCtx.resume();
    } catch (e) { audioCtx = null; }
  }

  async function enable() {
    localStorage.setItem(ENABLED_KEY, '1');
    unlockAudio();
    refreshButton();
    chime(); // نغمة تجريبية ليتأكد المستخدم أن الصوت يعمل

    if (PushDevice.needsHomeScreen()) {
      notice('📲 خطوة واحدة للآيفون', 'لاستقبال الإشعارات واللوحة مغلقة: اضغط زر المشاركة ⬆️ ثم "إضافة إلى الشاشة الرئيسية"، وافتح اللوحة من الأيقونة وفعّل الإشعارات من هناك.');
      return;
    }
    if ('Notification' in window && Notification.permission === 'default') {
      try { await Notification.requestPermission(); } catch (e) { /* غير مدعوم */ }
    }
    if ('Notification' in window && Notification.permission === 'denied') {
      notice('🔕 الإشعارات محظورة', 'اسمح بالإشعارات لهذا الموقع من إعدادات المتصفح (رمز القفل بجانب الرابط) ثم اضغط الزر مرة أخرى.');
      return;
    }

    const result = await PushDevice.subscribe();
    if (result === 'ok') {
      // إشعار تجريبي حقيقي من الخادم — يثبت أن السلسلة كاملة تعمل على هذا الجهاز
      adminRequest('/push/test', { method: 'POST' }).catch(() => {});
      notice('✅ تم تفعيل الإشعارات', 'سيصلك إشعار على هذا الجهاز مع كل طلب جديد حتى لو كانت لوحة التحكم مغلقة.');
    } else if (result === 'server') {
      notice('🔔 التنبيه يعمل أثناء فتح اللوحة فقط', 'لتفعيل الإشعارات واللوحة مغلقة: يجب ضبط مفاتيح VAPID على الخادم (php artisan push:vapid).');
    } else if (result === 'unsupported') {
      notice('🔔 التنبيه يعمل أثناء فتح اللوحة فقط', 'هذا المتصفح لا يدعم الإشعارات في الخلفية — استخدم Chrome أو Edge أو Safari حديث.');
    } else if (result === 'error') {
      notice('⚠️ تعذّر تسجيل الجهاز', 'تحقق من الاتصال بالإنترنت ثم اضغط الزر مرة أخرى (إيقاف ثم تفعيل).');
    }
  }

  async function disable() {
    localStorage.setItem(ENABLED_KEY, '0');
    refreshButton();
    await PushDevice.unsubscribe();
  }

  function start() {
    refreshButton();
    document.getElementById('notify-btn')?.addEventListener('click', () => {
      if (isOn()) { disable(); } else { enable(); }
    });
    // مزامنة اشتراك الجهاز مرة في كل جلسة (يُجدَّد إن تغيّرت المفاتيح أو حُذف من الخادم)
    if (isOn() && !sessionStorage.getItem('diwan_push_synced') && 'Notification' in window && Notification.permission === 'granted') {
      PushDevice.subscribe().then((r) => { if (r === 'ok') sessionStorage.setItem('diwan_push_synced', '1'); });
    }
    document.addEventListener('pointerdown', unlockAudio, { once: true });

    poll();
    clearInterval(timer);
    timer = setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
  }

  return { start };
})();

function formatPrice(n) { return new Intl.NumberFormat('ar-YE').format(n) + ' ريال'; }

const STATUS_LABELS = {
  pending: 'جديد', confirmed: 'تم التأكيد', processing: 'قيد التجهيز',
  shipped: 'تم الشحن', delivered: 'تم التسليم', cancelled: 'ملغي',
};
