/**
 * admin.js — طبقة مشتركة للوحة التحكم: المصادقة، استدعاء الـ API، الهيكل العام (Sidebar)
 */
const ADMIN_API_BASE = window.DIWAN_API_BASE || '/api';

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

function requireAdminAuth() {
  if (!AdminAuth.isLoggedIn()) window.location.href = '/admin';
}

function renderSidebar(active) {
  const el = document.getElementById('admin-sidebar');
  if (!el) return;
  const items = [
    { href: '/admin/dashboard', label: 'لوحة القيادة', key: 'dashboard' },
    { href: '/admin/products', label: 'المنتجات', key: 'products' },
    { href: '/admin/orders', label: 'الطلبات', key: 'orders' },
    { href: '/admin/customers', label: 'العملاء', key: 'customers' },
    { href: '/admin/categories', label: 'التصنيفات', key: 'categories' },
    { href: '/admin/payments', label: 'الدفعات', key: 'payments' },
    { href: '/admin/settings', label: 'الإعدادات', key: 'settings' },
  ];
  el.innerHTML = `
    <div class="brand">متجر ديوان الأصالة</div>
    <nav>${items.map(i => `<a href="${i.href}" class="${active === i.key ? 'active' : ''}">${i.label}</a>`).join('')}</nav>
    <button class="logout-btn" id="admin-logout-btn">تسجيل الخروج</button>
  `;
  document.getElementById('admin-logout-btn').addEventListener('click', () => {
    AdminAuth.clear();
    window.location.href = '/admin';
  });
}

function formatPrice(n) { return new Intl.NumberFormat('ar-YE').format(n) + ' ريال'; }

const STATUS_LABELS = {
  pending: 'جديد', confirmed: 'تم التأكيد', processing: 'قيد التجهيز',
  shipped: 'تم الشحن', delivered: 'تم التسليم', cancelled: 'ملغي',
};
