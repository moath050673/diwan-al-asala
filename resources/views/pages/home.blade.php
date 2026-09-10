@extends('layouts.app')

@section('title', 'متجر ديوان الأصالة | عطور وبخور وزباد بروح الأصالة اليمنية')

@section('content')
<section class="hero">
  <div class="container hero-content">
    <span class="hero-eyebrow">متجر ديوان الأصالة</span>
    <h1>عطور وبخور وزباد<br>بروح الأصالة اليمنية</h1>
    <p>منتجات أصيلة مختارة بعناية، تجمع بين عبق التراث اليمني وفخامة العطور الشرقية.</p>
    <div class="hero-actions">
      <a href="/products" class="btn btn-primary">تسوق الآن</a>
      <a href="#" id="hero-wa-btn" class="btn btn-whatsapp" target="_blank" rel="noopener">تواصل معنا عبر واتساب</a>
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
