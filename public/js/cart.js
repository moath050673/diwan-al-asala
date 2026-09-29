/* ===================================================================
   cart.js — إدارة سلة المشتريات (تُخزَّن محليًا لدى العميل حتى إتمام الطلب،
   وعند إتمام الطلب يعاد حساب الأسعار والإجمالي من الـ Backend وليس من هنا)
   =================================================================== */

const Cart = (() => {
  const STORAGE_KEY = 'diwan_cart_v1';

  function getAll() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    } catch { return []; }
  }

  function save(items) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch (e) { /* التخزين ممتلئ أو معطّل (وضع التصفح الخاص) — السلة تبقى للصفحة الحالية فقط */ }
    updateCartCountBadge();
  }

  function add(product, qty = 1) {
    const items = getAll();
    const existing = items.find(i => i.id === product.id);
    if (existing) {
      existing.qty += qty;
    } else {
      items.push({
        id: product.id, name: product.name, price: product.price,
        image: product.thumb || product.image || null, qty
      });
    }
    save(items);
    return items;
  }

  /**
   * يحدّث السلة من بيانات المتجر الحالية: الاسم والسعر والصورة، ويحذف المنتجات
   * التي لم تعد متاحة. السعر النهائي يحسبه الخادم دائمًا — هذا فقط حتى يرى العميل
   * نفس الأرقام التي سيُحاسب عليها.
   * @returns {Promise<{items: Array, removed: string[], changed: boolean}>}
   */
  async function syncWithStore() {
    const items = getAll();
    if (!items.length) return { items, removed: [], changed: false };

    const products = await Products.loadAll();
    if (Products.loadFailed()) return { items, removed: [], changed: false }; // لا نحذف شيئًا بدون بيانات مؤكدة

    const byId = new Map(products.map(p => [String(p.id), p]));
    const removed = [];
    let changed = false;
    const next = [];
    for (const item of items) {
      const p = byId.get(String(item.id));
      if (!p) { removed.push(item.name); continue; }
      const updated = { ...item, name: p.name, price: p.price, image: p.thumb || p.image || null };
      if (updated.name !== item.name || updated.price !== item.price || updated.image !== item.image) changed = true;
      next.push(updated);
    }
    if (removed.length || changed) save(next);

    return { items: next, removed, changed };
  }

  function updateQty(id, qty) {
    let items = getAll();
    items = items.map(i => (i.id == id ? { ...i, qty: Math.max(1, qty) } : i));
    save(items);
    return items;
  }

  function remove(id) {
    const items = getAll().filter(i => i.id != id);
    save(items);
    return items;
  }

  function clear() { save([]); }

  function totals(items = getAll(), shippingCost = 0) {
    const subtotal = items.reduce((sum, i) => sum + i.price * i.qty, 0);
    const total = subtotal + shippingCost;
    return { subtotal, shippingCost, total, count: items.reduce((s, i) => s + i.qty, 0) };
  }

  function updateCartCountBadge() {
    const count = totals().count;
    document.querySelectorAll('.cart-count').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? 'flex' : 'none';
    });
  }

  document.addEventListener('DOMContentLoaded', updateCartCountBadge);

  /** رسالة للعميل إن تغيّرت السلة بعد المزامنة */
  function syncNotice({ removed, changed }) {
    if (removed.length) showToast(`لم يعد متاحًا وتمت إزالته من السلة: ${removed.join('، ')}`, 'error');
    else if (changed) showToast('تم تحديث أسعار السلة حسب أحدث أسعار المتجر', 'success');
  }

  return { getAll, add, updateQty, remove, clear, totals, updateCartCountBadge, syncWithStore, syncNotice };
})();
