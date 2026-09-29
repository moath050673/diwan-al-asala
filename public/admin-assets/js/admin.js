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
  return res.json();
}

/** رفع ملفات (FormData) — بدون Content-Type حتى يضيف المتصفح حدود الملف (boundary) */
async function adminUpload(path, formData) {
  const res = await fetch(ADMIN_API_BASE + path, {
    method: 'POST',
    body: formData,
    headers: { Accept: 'application/json', Authorization: `Bearer ${AdminAuth.getToken()}` },
  });
  if (res.status === 401) { AdminAuth.clear(); window.location.href = '/admin'; return null; }
  return res.json().catch(() => ({ success: false, message: 'تعذّر رفع الملف' }));
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
   OrderAlerts — تنبيه فوري بالطلبات الجديدة أثناء فتح لوحة التحكم
   (جوال أو كمبيوتر): صوت + إشعار المتصفح + اهتزاز + رسالة على الشاشة
   + عدد الطلبات الجديدة بجانب "الطلبات" وفي عنوان التبويب.
   للتنبيه واللوحة مغلقة: استخدم Telegram (راجع TELEGRAM_* في .env).
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

  function toast(order, isSummary = false) {
    let stack = document.querySelector('.toast-stack');
    if (!stack) { stack = document.createElement('div'); stack.className = 'toast-stack'; document.body.appendChild(stack); }
    while (stack.children.length >= 4) stack.firstElementChild.remove();
    const el = document.createElement('div');
    el.className = 'admin-toast';
    el.setAttribute('role', 'alert');
    el.innerHTML = (isSummary ? `
      <div>
        <strong>🔔 ${escapeHtml(order.orderNumber)}</strong>
        <a href="/admin/orders">${escapeHtml(order.customerName)} ←</a>
      </div>` : `
      <div>
        <strong>🔔 طلب جديد #${escapeHtml(order.orderNumber)}</strong>
        <div>${escapeHtml(order.customerName)} — ${formatPrice(order.total)}</div>
        <a href="/admin/orders?open=${encodeURIComponent(order.id)}">عرض الطلب ←</a>
      </div>`) + '<button class="close" type="button" aria-label="إغلاق">×</button>';
    el.querySelector('.close').addEventListener('click', () => el.remove());
    stack.appendChild(el);
    setTimeout(() => el.remove(), 20000);
  }

  function systemNotification(order) {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;
    try {
      const n = new Notification(`طلب جديد #${order.orderNumber}`, {
        body: `${order.customerName} — ${formatPrice(order.total)}`,
        icon: '/img/favicon.png',
        tag: 'order-' + order.id,
      });
      n.onclick = () => { window.focus(); window.location.href = '/admin/orders?open=' + order.id; };
    } catch (e) { /* بعض متصفحات الجوال تتطلب Service Worker — نكتفي بالتنبيه داخل الصفحة */ }
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
    if ('Notification' in window && Notification.permission === 'default') {
      try { await Notification.requestPermission(); } catch (e) { /* غير مدعوم */ }
    }
    refreshButton();
    chime(); // نغمة تجريبية ليتأكد المستخدم أن الصوت يعمل
  }

  function start() {
    refreshButton();
    document.getElementById('notify-btn')?.addEventListener('click', () => {
      if (isOn()) { localStorage.setItem(ENABLED_KEY, '0'); refreshButton(); } else { enable(); }
    });
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
