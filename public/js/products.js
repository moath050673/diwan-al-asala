/* ===================================================================
   products.js — بيانات تجريبية (Seed) + منطق عرض/فلترة المنتجات
   ملاحظة: هذه بيانات تجريبية Placeholder للتطوير فقط، ويجب استبدالها
   ببيانات حقيقية تُدار من لوحة التحكم (Admin Dashboard) بعد الربط بالـ API.
   =================================================================== */

const DIWAN_CATEGORIES = [
  { id: 1, slug: 'zabad', name: 'الزباد', icon: '🌿' },
  { id: 2, slug: 'bakhoor', name: 'البخور', icon: '🔥' },
  { id: 3, slug: 'perfume', name: 'العطور', icon: '🧴' },
  { id: 4, slug: 'offers', name: 'العروض', icon: '🏷️' },
  { id: 5, slug: 'new', name: 'المنتجات الجديدة', icon: '✨' },
];

// بيانات تجريبية — يمكن حذفها/استبدالها من لوحة التحكم
const DIWAN_PRODUCTS_SEED = [
  { id: 1, category: 'perfume', categoryName: 'العطور', name: 'عطر الأصالة الملكي', price: 15000, oldPrice: 18000, stock: 12, featured: true, rating: 4.8, sku: 'PRF-001', desc: 'عطر شرقي فاخر بتركيبة أصيلة تجمع بين العود والمسك، يدوم طويلًا ويناسب جميع المناسبات.' },
  { id: 2, category: 'bakhoor', categoryName: 'البخور', name: 'بخور فاخر', price: 8500, oldPrice: null, stock: 20, featured: true, rating: 4.6, sku: 'BKH-001', desc: 'بخور يمني أصيل معد يدويًا من أجود أنواع العود الطبيعي.' },
  { id: 3, category: 'zabad', categoryName: 'الزباد', name: 'زباد أصلي', price: 22000, oldPrice: 25000, stock: 5, featured: true, rating: 5.0, sku: 'ZBD-001', desc: 'زباد طبيعي فاخر يُستخدم كثابت للعطور ويمنحها رائحة أصيلة تدوم.' },
  { id: 4, category: 'perfume', categoryName: 'العطور', name: 'عطر شرقي فاخر', price: 12000, oldPrice: null, stock: 0, featured: false, rating: 4.4, sku: 'PRF-002', desc: 'مزيج شرقي دافئ من الفانيليا والعنبر.' },
  { id: 5, category: 'bakhoor', categoryName: 'البخور', name: 'بخور يمني فاخر', price: 9500, oldPrice: 11000, stock: 15, featured: false, rating: 4.7, sku: 'BKH-002', desc: 'بخور يمني تقليدي بنكهة مميزة وعبق فاخر.' },
  { id: 6, category: 'zabad', categoryName: 'الزباد', name: 'خلطة متجر ديوان الأصالة', price: 30000, oldPrice: null, stock: 8, featured: true, rating: 4.9, sku: 'MIX-001', desc: 'خلطة حصرية من متجر ديوان الأصالة تجمع الزباد والعود والمسك.' },
];

const Products = (() => {
  let cache = null;

  async function loadAll() {
    if (cache) return cache;
    const apiResult = await API.getProducts();
    cache = apiResult && apiResult.data ? apiResult.data : DIWAN_PRODUCTS_SEED;
    return cache;
  }

  async function getById(id) {
    const all = await loadAll();
    return all.find(p => String(p.id) === String(id));
  }

  function filterAndSort(list, { category, q, minPrice, maxPrice, sort } = {}) {
    let result = [...list];
    if (category) result = result.filter(p => p.category === category);
    if (q) {
      const query = q.trim().toLowerCase();
      result = result.filter(p =>
        p.name.toLowerCase().includes(query) ||
        p.sku.toLowerCase().includes(query) ||
        p.categoryName.toLowerCase().includes(query) ||
        (p.desc || '').toLowerCase().includes(query)
      );
    }
    if (minPrice) result = result.filter(p => p.price >= Number(minPrice));
    if (maxPrice) result = result.filter(p => p.price <= Number(maxPrice));

    switch (sort) {
      case 'price_asc': result.sort((a, b) => a.price - b.price); break;
      case 'price_desc': result.sort((a, b) => b.price - a.price); break;
      case 'newest': result.sort((a, b) => b.id - a.id); break;
      default: break;
    }
    return result;
  }

  function formatPrice(n) {
    return new Intl.NumberFormat('ar-YE').format(n) + ' ريال';
  }

  // أيقونات Placeholder أنيقة متناسقة مع هوية "ديوان الأصالة" — تُستخدم فقط
  // عند عدم وجود صورة حقيقية للمنتج، أو إذا تعذّر تحميل الصورة (رابط معطوب).
  const CATEGORY_PLACEHOLDER_ICONS = {
    bakhoor: `<svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M30 70h40l-6 18H36l-6-18Z"/><path d="M50 70V50"/><path d="M50 12c-6 8-6 14 0 20 6-6 6-12 0-20Z"/><path d="M38 18c-4 6-4 11 0 16"/><path d="M62 18c4 6 4 11 0 16"/></svg>`,
    perfume: `<svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="34" y="38" width="32" height="46" rx="4"/><path d="M42 38V26h16v12"/><rect x="45" y="14" width="10" height="12" rx="2"/><path d="M34 55h32"/></svg>`,
    zabad: `<svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="50" cy="55" rx="22" ry="28"/><path d="M50 27v-9"/><circle cx="50" cy="14" r="4"/></svg>`,
    default: `<svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="24" y="24" width="52" height="52" rx="6"/><path d="M24 62l16-16 12 12 12-14 12 18"/><circle cx="38" cy="38" r="4"/></svg>`,
  };

  function placeholderMarkup(category) {
    const icon = CATEGORY_PLACEHOLDER_ICONS[category] || CATEGORY_PLACEHOLDER_ICONS.default;
    return `<div class="product-thumb-placeholder">${icon}</div>`;
  }

  // يُستدعى تلقائيًا عبر onerror إذا تعذّر تحميل رابط الصورة (رابط معطوب/محذوف)
  // — يستبدلها بالـ Placeholder الأنيق بدل ترك أيقونة "صورة مكسورة" من المتصفح.
  function handleThumbImgError(imgEl, category) {
    const wrapper = imgEl.closest('.product-thumb');
    if (wrapper) wrapper.innerHTML = placeholderMarkup(category);
  }
  window.handleThumbImgError = handleThumbImgError;

  function productThumbHTML(p) {
    if (p.image) {
      return `<img src="${p.image}" alt="${p.name}" class="product-thumb-img" loading="lazy" onerror="handleThumbImgError(this,'${p.category}')">`;
    }
    return placeholderMarkup(p.category);
  }

  function productCardHTML(p) {
    const discount = p.oldPrice ? Math.round(100 - (p.price / p.oldPrice) * 100) : null;
    const outOfStock = p.stock <= 0;
    return `
    <div class="product-card" data-id="${p.id}">
      ${discount ? `<span class="badge">خصم ${discount}%</span>` : ''}
      ${outOfStock ? `<span class="badge-out">نفد المخزون</span>` : ''}
      <a href="/product/${p.id}" class="product-thumb">${productThumbHTML(p)}</a>
      <button class="fav-btn" data-fav="${p.id}" aria-label="أضف للمفضلة">♡</button>
      <div class="product-body">
        <span class="product-cat">${p.categoryName}</span>
        <a href="/product/${p.id}"><h3 class="product-name">${p.name}</h3></a>
        <span class="product-rating">${'★'.repeat(Math.round(p.rating))}${'☆'.repeat(5 - Math.round(p.rating))} <small>(${p.rating})</small></span>
        <div class="product-price">
          <span class="price-now">${formatPrice(p.price)}</span>
          ${p.oldPrice ? `<span class="price-old">${formatPrice(p.oldPrice)}</span>` : ''}
        </div>
        <span class="stock-note ${outOfStock ? 'out' : ''}">${outOfStock ? 'نفد من المخزون' : 'متوفر في المخزون'}</span>
      </div>
      <div class="product-actions">
        <button class="btn btn-primary btn-add-cart" data-id="${p.id}" ${outOfStock ? 'disabled' : ''}>أضف إلى السلة</button>
        <button class="btn btn-whatsapp btn-wa-inquire" data-id="${p.id}">واتساب</button>
      </div>
    </div>`;
  }

  return { loadAll, getById, filterAndSort, formatPrice, productCardHTML, categories: DIWAN_CATEGORIES };
})();
