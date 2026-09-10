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
    localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
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
        image: product.image || null, qty
      });
    }
    save(items);
    return items;
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

  return { getAll, add, updateQty, remove, clear, totals, updateCartCountBadge };
})();
