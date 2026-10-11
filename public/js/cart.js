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

  /** أقصى كمية مسموحة لسطر في السلة = المخزون المتوفر (إن كان معروفًا) */
  function maxQty(item) {
    return Number.isFinite(item.stock) ? Math.max(0, item.stock) : Infinity;
  }

  /**
   * يضيف منتجًا للسلة دون تجاوز المخزون المتوفر.
   * @returns {{added: number, inCart: number, stock: number}} الكمية المضافة فعلًا
   */
  function add(product, qty = 1) {
    const items = getAll();
    const stock = Number(product.stock);
    let existing = items.find(i => String(i.id) === String(product.id));
    if (!existing) {
      existing = { id: product.id, name: product.name, price: product.price, image: product.thumb || product.image || null, qty: 0 };
      items.push(existing);
    }
    existing.stock = stock;
    const before = existing.qty;
    existing.qty = Math.min(before + Math.max(1, Math.floor(qty) || 1), maxQty(existing));
    const added = existing.qty - before;
    save(items.filter(i => i.qty > 0));
    return { added, inCart: existing.qty, stock };
  }

  /** رسالة موحّدة بعد الإضافة للسلة (تنبيه العميل إن وصل لحد المخزون) */
  function addNotice(product, result) {
    if (result.added <= 0) {
      showToast(result.stock > 0
        ? `لديك في السلة كل الكمية المتوفرة من "${product.name}" (${result.stock} قطعة)`
        : `"${product.name}" نفد من المخزون`, 'error');
    } else if (result.inCart >= result.stock) {
      showToast(`تمت إضافة "${product.name}" — وصلت للحد الأقصى المتوفر (${result.stock} قطعة)`, 'success');
    } else {
      showToast(`تمت إضافة "${product.name}" إلى السلة`, 'success');
    }
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
    const limited = [];
    let changed = false;
    let dirty = false;
    const next = [];
    for (const item of items) {
      const p = byId.get(String(item.id));
      // غير موجود أو نفد مخزونه ← يُحذف من السلة
      if (!p || Number(p.stock) <= 0) { removed.push(item.name); continue; }
      const stock = Number(p.stock);
      const qty = Math.min(item.qty, stock);
      if (qty < item.qty) limited.push(`${p.name} (${stock})`);
      const updated = { ...item, name: p.name, price: p.price, image: p.thumb || p.image || null, stock, qty };
      if (updated.name !== item.name || updated.price !== item.price || updated.image !== item.image) changed = true;
      if (changed || qty !== item.qty || stock !== item.stock) dirty = true;
      next.push(updated);
    }
    if (removed.length || dirty) save(next);

    return { items: next, removed, changed, limited };
  }

  /** يغيّر كمية سطر بين 1 والمخزون المتوفر */
  function updateQty(id, qty) {
    let items = getAll();
    items = items.map(i => (i.id == id ? { ...i, qty: Math.max(1, Math.min(Math.floor(qty) || 1, maxQty(i))) } : i));
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
  function syncNotice({ removed, changed, limited = [] }) {
    if (removed.length) showToast(`لم يعد متاحًا وتمت إزالته من السلة: ${removed.join('، ')}`, 'error');
    else if (limited.length) showToast(`تم تعديل الكمية حسب المتوفر في المخزون: ${limited.join('، ')}`, 'error');
    else if (changed) showToast('تم تحديث أسعار السلة حسب أحدث أسعار المتجر', 'success');
  }

  return { getAll, add, addNotice, maxQty, updateQty, remove, clear, totals, updateCartCountBadge, syncWithStore, syncNotice };
})();
