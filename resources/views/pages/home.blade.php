@extends('layouts.app')

@section('title', 'متجر ديوان الأصالة | عطور وبخور وزباد بروح الأصالة اليمنية')

@section('content')
<section class="hero">
  <div class="container hero-inner">
    <div class="hero-content">
      <span class="hero-eyebrow">متجر ديوان الأصالة</span>
      <h1>عطور وبخور وزباد<br>بروح الأصالة اليمنية</h1>
      <p>منتجات أصيلة مختارة بعناية، تجمع بين عبق التراث اليمني وفخامة العطور الشرقية.</p>
      <div class="hero-actions">
        <a href="/products" class="btn btn-primary">تسوق الآن</a>
        <a href="#" id="hero-wa-btn" class="btn btn-whatsapp" target="_blank" rel="noopener">تواصل معنا عبر واتساب</a>
      </div>
    </div>
    <div class="hero-visual">
      <svg class="mabkhara-illustration" viewBox="0 0 240 400" xmlns="http://www.w3.org/2000/svg">
        <path class="smoke-wisp w1" d="M120,140 C108,110 136,88 114,58 C98,32 132,18 116,-12"/>
        <path class="smoke-wisp w2" d="M120,138 C132,113 104,93 123,63 C138,38 106,14 121,-16"/>
        <path class="smoke-wisp w3" d="M118,140 C126,100 106,78 129,44 C142,8 110,-8 126,-32"/>
        <ellipse cx="120" cy="292" rx="58" ry="13" fill="var(--color-bg)" stroke="var(--color-primary)" stroke-width="2"/>
        <path d="M88,282 L98,232 L142,232 L152,282 Z" fill="var(--color-surface-elevated)" stroke="var(--color-primary)" stroke-width="2"/>
        <path d="M68,232 C68,168 66,138 120,138 C174,138 172,168 172,232 C172,248 68,248 68,232 Z" fill="var(--color-surface-elevated)" stroke="var(--color-primary)" stroke-width="2.5"/>
        <circle cx="95" cy="195" r="5" fill="var(--color-primary)"/>
        <circle cx="120" cy="185" r="5" fill="var(--color-primary)"/>
        <circle cx="145" cy="195" r="5" fill="var(--color-primary)"/>
        <circle cx="95" cy="218" r="5" fill="var(--color-primary)"/>
        <circle cx="120" cy="225" r="5" fill="var(--color-primary)"/>
        <circle cx="145" cy="218" r="5" fill="var(--color-primary)"/>
        <path d="M92,138 C92,110 148,110 148,138 Z" fill="var(--color-primary)" opacity="0.9"/>
        <rect x="116" y="98" width="8" height="18" rx="3" fill="var(--color-primary)"/>
        <circle cx="120" cy="92" r="9" fill="var(--color-primary)"/>
      </svg>
    </div>
  </div>
</section>
<section class="section">
  <div class="container">
    <div class="section-title">
      <span class="eyebrow">تصفح حسب</span>
      <h2>التصنيفات</h2>
      <p>اختر من بين تشكيلتنا المميزة من العطور والبخور والزباد</p>
    </div>
    <div class="categories-grid" id="categories-grid"></div>
  </div>
</section>

<section class="section" style="background:var(--white);">
  <div class="container">
    <div class="section-title">
      <span class="eyebrow">الأكثر تميزًا</span>
      <h2>منتجات مميزة</h2>
      <p>مختارات متجر ديوان الأصالة من أفخم المنتجات</p>
    </div>
    <div class="products-grid" id="featured-products"></div>
  </div>
</section>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', async () => {
    renderHeader('home');
    renderFooter();
    document.getElementById('hero-wa-btn').href = WhatsAppLink.general();

    const catGrid = document.getElementById('categories-grid');
    catGrid.innerHTML = Products.categories.map(c => `
      <div class="category-card">
        <div class="cat-img">${c.icon}</div>
        <h3>${c.name}</h3>
        <a class="view-link" href="/products?category=${c.slug}">عرض المنتجات ←</a>
      </div>
    `).join('');

    const all = await Products.loadAll();
    const featured = all.filter(p => p.featured);
    document.getElementById('featured-products').innerHTML =
      featured.map(Products.productCardHTML).join('') || '<p>لا توجد منتجات مميزة حاليًا.</p>';
  });
</script>
@endpush
