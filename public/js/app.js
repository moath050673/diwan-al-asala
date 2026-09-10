/* ===================================================================
   app.js — تهيئة عامة: الهيدر، الفوتر، القائمة الجانبية للجوال، Toast،
   زر واتساب العائم. تُحقن هذه المكوّنات في كل صفحة عبر #site-header/#site-footer
   لضمان تعديلها من مكان واحد.
   =================================================================== */

// إعدادات المتجر (Placeholder — يجب جلبها من API الإعدادات عند الربط بالخادم)
window.DIWAN_SETTINGS = {
  storeName: 'متجر ديوان الأصالة',
  whatsappNumber: '785149261', // ضعه في .env / لوحة التحكم عند النشر
  facebookUrl: 'https://facebook.com/',
  instagramUrl: 'https://instagram.com/',
  currency: 'ريال يمني',
  shippingCost: 1000,
};

function renderHeader(activePage = '') {
  const el = document.getElementById('site-header');
  if (!el) return;
  el.innerHTML = `
    <div class="header-top">توصيل داخل صنعاء 🚚 | تواصل معنا عبر واتساب للاستفسار الفوري</div>
    <div class="container header-main">
      <a href="/" class="logo">
        <img src="/img/logo.png" alt="ديوان الأصالة">
        <span>عطور · بخور · زباد</span>
      </a>
      <nav class="main-nav" id="main-nav">
        <a href="/" class="${activePage === 'home' ? 'active' : ''}">الرئيسية</a>
        <a href="/products" class="${activePage === 'products' ? 'active' : ''}">المنتجات</a>
        <a href="/categories" class="${activePage === 'categories' ? 'active' : ''}">التصنيفات</a>
        <a href="/about" class="${activePage === 'about' ? 'active' : ''}">من نحن</a>
        <a href="/contact" class="${activePage === 'contact' ? 'active' : ''}">تواصل معنا</a>
      </nav>
      <button class="icon-btn nav-toggle-btn" id="nav-toggle-btn" aria-label="إخفاء/إظهار القائمة" title="إخفاء/إظهار القائمة">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="global-search" placeholder="ابحث عن عطر، بخور، زباد..." />
      </div>
      <div class="header-actions">
        <a class="icon-btn" href="${WhatsAppLink.general()}" target="_blank" rel="noopener" aria-label="واتساب">
          <svg viewBox="0 0 24 24" width="19" height="19" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.87.5 3.6 1.4 5.1L2 22l5.15-1.5a9.9 9.9 0 0 0 4.9 1.28c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm0 18.05c-1.6 0-3.1-.44-4.4-1.2l-.31-.18-3.06.9.9-2.98-.2-.32a8.16 8.16 0 0 1-1.24-4.36c0-4.53 3.7-8.23 8.3-8.23 4.6 0 8.3 3.7 8.3 8.23 0 4.53-3.7 8.14-8.3 8.14Z"/></svg>
        </a>
        <a class="icon-btn" href="/cart" aria-label="السلة">
          <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h2l2.4 12.4a2 2 0 0 0 2 1.6h8.4a2 2 0 0 0 2-1.6L22 7H6"/><circle cx="9" cy="20" r="1.2"/><circle cx="18" cy="20" r="1.2"/></svg>
          <span class="cart-count">0</span>
        </a>
        <button class="hamburger" id="hamburger-btn" aria-label="القائمة">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
    <div class="mobile-nav-overlay" id="mobile-overlay"></div>
    <nav class="mobile-nav" id="mobile-nav">
      <div class="mobile-close" id="mobile-close">✕</div>
      <a href="/">الرئيسية</a>
      <a href="/products">المنتجات</a>
      <a href="/categories">التصنيفات</a>
      <a href="/about">من نحن</a>
      <a href="/contact">تواصل معنا</a>
      <a href="/cart">السلة</a>
      <a href="${WhatsAppLink.general()}" target="_blank" rel="noopener">تواصل عبر واتساب</a>
    </nav>
  `;

  const hamburger = document.getElementById('hamburger-btn');
  const mobileNav = document.getElementById('mobile-nav');
  const overlay = document.getElementById('mobile-overlay');
  const closeBtn = document.getElementById('mobile-close');
  const openMenu = () => { mobileNav.classList.add('open'); overlay.classList.add('open'); };
  const closeMenu = () => { mobileNav.classList.remove('open'); overlay.classList.remove('open'); };
  hamburger?.addEventListener('click', openMenu);
  overlay?.addEventListener('click', closeMenu);
  closeBtn?.addEventListener('click', closeMenu);

  // زر إخفاء/إظهار القائمة الرئيسية على شاشات الكمبيوتر (بناءً على طلب مباشر،
  // رغم أن إبقاءها ظاهرة دائمًا هو المعتاد في أغلب المواقع). نحفظ التفضيل
  // في المتصفح حتى يبقى نفس الاختيار عند التنقل بين الصفحات.
  const mainNav = document.getElementById('main-nav');
  const navToggleBtn = document.getElementById('nav-toggle-btn');
  const applyNavVisibility = () => {
    const hidden = localStorage.getItem('diwan_nav_hidden') === '1';
    mainNav?.classList.toggle('collapsed', hidden);
  };
  navToggleBtn?.addEventListener('click', () => {
    const isHidden = mainNav?.classList.toggle('collapsed');
    localStorage.setItem('diwan_nav_hidden', isHidden ? '1' : '0');
  });
  applyNavVisibility();

  const searchInput = document.getElementById('global-search');
  searchInput?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && searchInput.value.trim()) {
      window.location.href = `/products?q=${encodeURIComponent(searchInput.value.trim())}`;
    }
  });

  Cart.updateCartCountBadge();
}

function renderFooter() {
  const el = document.getElementById('site-footer');
  if (!el) return;
  const s = window.DIWAN_SETTINGS;
  el.innerHTML = `
    <div class="container footer-grid">
      <div class="footer-col">
        <h4 style="display:flex; align-items:center; gap:10px;"><img src="/img/logo.png" alt="ديوان الأصالة" style="height:34px; width:auto;"> متجر ديوان الأصالة</h4>
        <p>العطور | البخور | الزباد</p>
        <p>متجر متخصص في تقديم منتجات أصيلة ذات جودة عالية لعملائنا في صنعاء واليمن.</p>
        <div class="social-icons">
          <a href="${s.facebookUrl}" target="_blank" rel="noopener" aria-label="فيسبوك">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5 3.66 9.15 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56v1.87h2.78l-.44 2.9h-2.34V22c4.78-.79 8.44-4.94 8.44-9.94Z"/></svg>
          </a>
          <a href="${s.instagramUrl}" target="_blank" rel="noopener" aria-label="انستقرام">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1" fill="currentColor" stroke="none"/></svg>
          </a>
          <a href="${WhatsAppLink.general()}" target="_blank" rel="noopener" aria-label="واتساب">
            <svg viewBox="0 0 24 24" width="17" height="17" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.87.5 3.6 1.4 5.1L2 22l5.15-1.5a9.9 9.9 0 0 0 4.9 1.28c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm0 18.05c-1.6 0-3.1-.44-4.4-1.2l-.31-.18-3.06.9.9-2.98-.2-.32a8.16 8.16 0 0 1-1.24-4.36c0-4.53 3.7-8.23 8.3-8.23 4.6 0 8.3 3.7 8.3 8.23 0 4.53-3.7 8.14-8.3 8.14Z"/></svg>
          </a>
        </div>
      </div>
      <div class="footer-col">
        <h4>روابط سريعة</h4>
        <a href="/">الرئيسية</a>
        <a href="/products">المنتجات</a>
        <a href="/about">من نحن</a>
        <a href="/contact">تواصل معنا</a>
      </div>
      <div class="footer-col">
        <h4>سياسات</h4>
        <a href="/privacy">سياسة الخصوصية</a>
        <a href="/terms">الشروط والأحكام</a>
      </div>
      <div class="footer-col">
        <h4>تواصل معنا</h4>
        <p>صنعاء، اليمن</p>
        <p>يوميًا من 9 صباحًا حتى 10 مساءً</p>
        <a href="${WhatsAppLink.general()}" target="_blank" rel="noopener">راسلنا عبر واتساب</a>
      </div>
    </div>
    <div class="footer-bottom">© ${new Date().getFullYear()} متجر ديوان الأصالة - جميع الحقوق محفوظة</div>
  `;
}

function renderWhatsAppFloat() {
  const el = document.getElementById('whatsapp-float');
  if (!el) return;
  el.href = WhatsAppLink.general();
  el.target = '_blank';
  el.rel = 'noopener';
}

/* ---------- Toast ---------- */
function showToast(message, type = 'success') {
  let wrap = document.querySelector('.toast-wrap');
  if (!wrap) {
    wrap = document.createElement('div');
    wrap.className = 'toast-wrap';
    document.body.appendChild(wrap);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.textContent = message;
  wrap.appendChild(toast);
  setTimeout(() => toast.remove(), 2800);
}

/* ---------- Delegated cart-add / whatsapp-inquire handlers (used across pages) ---------- */
document.addEventListener('click', async (e) => {
  const addBtn = e.target.closest('.btn-add-cart');
  if (addBtn) {
    const id = addBtn.dataset.id;
    const product = await Products.getById(id);
    if (product) {
      Cart.add(product, 1);
      showToast(`تمت إضافة "${product.name}" إلى السلة`, 'success');
    }
  }
  const waBtn = e.target.closest('.btn-wa-inquire');
  if (waBtn) {
    const id = waBtn.dataset.id;
    const product = await Products.getById(id);
    if (product) window.open(WhatsAppLink.productInquiry(product), '_blank');
  }
  const favBtn = e.target.closest('.fav-btn');
  if (favBtn) {
    favBtn.textContent = favBtn.textContent === '♡' ? '♥' : '♡';
  }
});

document.addEventListener('DOMContentLoaded', () => {
  renderWhatsAppFloat();
});
