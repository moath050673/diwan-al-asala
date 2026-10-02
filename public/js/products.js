/* ===================================================================
   products.js — تحميل المنتجات والتصنيفات من الـ API + منطق العرض والفلترة.
   لا توجد بيانات تجريبية: عند تعذّر الاتصال تُعرض رسالة خطأ مع زر إعادة المحاولة
   (عرض منتجات وهمية بأسعار غير حقيقية كان سيضلل العميل).
   =================================================================== */

// أيقونات التصنيفات المعروفة؛ أي تصنيف جديد يُضاف من لوحة التحكم يأخذ الأيقونة الافتراضية
const CATEGORY_ICONS = { zabad: '🌿', bakhoor: '🔥', perfume: '🧴' };
const DEFAULT_CATEGORY_ICON = '✨';

const Products = (() => {
  let cache = null;
  let failed = false;

  async function loadAll() {
    if (cache) return cache;
    // الفلترة تتم في المتصفح لذا نطلب الحد الأقصى (100)
    const apiResult = await API.getProducts('?limit=100');
    failed = !(apiResult && Array.isArray(apiResult.data));
    if (failed) return []; // لا نخزّن الفشل — المحاولة التالية تعيد الطلب
    cache = apiResult.data;
    return cache;
  }

  let categoriesCache = null;

  // التصنيفات النشطة من قاعدة البيانات (نفس ما يُدار من لوحة التحكم ← التصنيفات)
  async function loadCategories() {
    if (categoriesCache) return categoriesCache;
    const apiResult = await API.getCategories();
    if (!(apiResult && Array.isArray(apiResult.data))) {
      failed = true;
      return [];
    }
    categoriesCache = apiResult.data.map(c => ({ ...c, icon: CATEGORY_ICONS[c.slug] || DEFAULT_CATEGORY_ICON }));
    return categoriesCache;
  }

  /** هل فشل آخر تحميل من الخادم؟ (لعرض رسالة خطأ بدل "لا توجد منتجات") */
  function loadFailed() { return failed; }

  function errorHTML() {
    return `
      <div class="empty-state" style="grid-column:1/-1;" role="alert">
        <div class="icon">⚠️</div>
        <h3>تعذّر تحميل المنتجات</h3>
        <p>تحقق من اتصالك بالإنترنت ثم أعد المحاولة.</p>
        <button type="button" class="btn btn-primary" style="margin-top:16px;" onclick="location.reload()">إعادة المحاولة</button>
      </div>`;
  }

  function categoryCardHTML(c) {
    return `
      <div class="category-card">
        <div class="cat-img">${c.icon}</div>
        <h3>${escapeHtml(c.name)}</h3>
        <a class="view-link" href="/products?category=${encodeURIComponent(c.slug)}">عرض المنتجات ←</a>
      </div>`;
  }

  async function getById(id) {
    const all = await loadAll();
    return all.find(p => String(p.id) === String(id));
  }

  // تفاصيل منتج واحد (تشمل الوصف) — قائمة المنتجات لا تحتوي على الوصف
  async function getDetails(id) {
    const apiResult = await API.getProduct(id);
    if (apiResult && apiResult.data) return { ...apiResult.data, desc: apiResult.data.description };
    return getById(id);
  }

  function ratingHTML(rating) {
    if (typeof rating !== 'number') return '';
    const r = Math.max(0, Math.min(5, Math.round(rating)));
    return `${'★'.repeat(r)}${'☆'.repeat(5 - r)} <small>(${rating})</small>`;
  }

  function filterAndSort(list, { category, q, minPrice, maxPrice, sort } = {}) {
    let result = [...list];
    if (category) result = result.filter(p => p.category === category);
    if (q) {
      // بحث العميل: باسم المنتج أو اسم التصنيف فقط
      const query = q.trim().toLowerCase();
      result = result.filter(p =>
        p.name.toLowerCase().includes(query) ||
        (p.categoryName || '').toLowerCase().includes(query)
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
      // القيمة داخل onerror هي كود JavaScript — نسمح فقط بمفاتيح معروفة بدل تهريبها
      const cat = Object.prototype.hasOwnProperty.call(CATEGORY_PLACEHOLDER_ICONS, p.category) ? p.category : 'default';
      // النسخة المصغّرة المحسّنة (أخف بكثير) — والأصلية إن لم تتوفر
      return `<img src="${escapeHtml(p.thumb || p.image)}" alt="${escapeHtml(p.name)}" class="product-thumb-img" loading="lazy" decoding="async" onerror="handleThumbImgError(this,'${cat}')">`;
    }
    return placeholderMarkup(p.category);
  }

  // على الجوال يظهر زر واتساب كأيقونة فقط (responsive.css) حتى يتسع زر السلة في البطاقة المدمجة
  const WA_ICON = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.87.5 3.6 1.4 5.1L2 22l5.15-1.5a9.9 9.9 0 0 0 4.9 1.28c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm0 18.05c-1.6 0-3.1-.44-4.4-1.2l-.31-.18-3.06.9.9-2.98-.2-.32a8.16 8.16 0 0 1-1.24-4.36c0-4.53 3.7-8.23 8.3-8.23 4.6 0 8.3 3.7 8.3 8.23 0 4.53-3.7 8.14-8.3 8.14Z"/></svg>';

  function productCardHTML(p) {
    const discount = p.oldPrice ? Math.round(100 - (p.price / p.oldPrice) * 100) : null;
    const outOfStock = p.stock <= 0;
    const id = encodeURIComponent(p.id);
    return `
    <div class="product-card" data-id="${id}">
      ${discount ? `<span class="badge">خصم ${discount}%</span>` : ''}
      ${outOfStock ? `<span class="badge-out">نفد المخزون</span>` : ''}
      <a href="/product/${id}" class="product-thumb">${productThumbHTML(p)}</a>
      <button class="fav-btn" data-fav="${id}" aria-label="أضف للمفضلة">♡</button>
      <div class="product-body">
        <span class="product-cat">${escapeHtml(p.categoryName)}</span>
        <a href="/product/${id}"><h3 class="product-name">${escapeHtml(p.name)}</h3></a>
        <span class="product-rating">${ratingHTML(p.rating)}</span>
        <div class="product-price">
          <span class="price-now">${formatPrice(p.price)}</span>
          ${p.oldPrice ? `<span class="price-old">${formatPrice(p.oldPrice)}</span>` : ''}
        </div>
        <span class="stock-note ${outOfStock ? 'out' : ''}">${outOfStock ? 'نفد من المخزون' : 'متوفر في المخزون'}</span>
      </div>
      <div class="product-actions">
        <button class="btn btn-primary btn-add-cart" data-id="${id}" ${outOfStock ? 'disabled' : ''}>أضف إلى السلة</button>
        <button class="btn btn-whatsapp btn-wa-inquire" data-id="${id}" aria-label="استفسار عبر واتساب">${WA_ICON}<span class="btn-label">واتساب</span></button>
      </div>
    </div>`;
  }

  return { loadAll, loadCategories, loadFailed, errorHTML, categoryCardHTML, getById, getDetails, ratingHTML, filterAndSort, formatPrice, productCardHTML, placeholderHTML: placeholderMarkup };
})();
