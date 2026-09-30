@extends('layouts.app')

@section('title', 'جميع المنتجات | متجر ديوان الأصالة')
@section('description', 'تصفح جميع منتجات متجر ديوان الأصالة من العطور والبخور والزباد، مع إمكانية البحث والفلترة حسب التصنيف والسعر.')

@section('content')
<div class="page-header">
  <div class="container">
    <h1>المنتجات</h1>
    <div class="breadcrumb"><a href="/">الرئيسية</a> / المنتجات</div>
  </div>
</div>

<div class="section">
  <div class="container">
    <div class="filters-bar">
      <div class="filter-group">
        <label for="f-search">بحث</label>
        <input type="text" id="f-search" placeholder="اسم المنتج أو التصنيف...">
      </div>
      <div class="filter-group">
        <label for="f-category">التصنيف</label>
        <select id="f-category"><option value="">جميع التصنيفات</option></select>
      </div>
      <div class="filter-group"><label for="f-min">السعر من</label><input type="number" id="f-min" placeholder="0" min="0" inputmode="numeric"></div>
      <div class="filter-group"><label for="f-max">السعر إلى</label><input type="number" id="f-max" placeholder="أي سعر" min="0" inputmode="numeric"></div>
      <div class="filter-group">
        <label for="f-sort">الترتيب</label>
        <select id="f-sort">
          <option value="">الافتراضي</option>
          <option value="newest">الأحدث</option>
          <option value="price_asc">السعر: الأقل أولًا</option>
          <option value="price_desc">السعر: الأعلى أولًا</option>
        </select>
      </div>
      <span class="results-count" id="results-count" aria-live="polite"></span>
    </div>
    <div class="products-grid" id="products-list"></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let ALL_PRODUCTS = [];
  function getQueryParams() { return Object.fromEntries(new URLSearchParams(window.location.search)); }

  function renderList() {
    const filters = {
      category: document.getElementById('f-category').value,
      q: document.getElementById('f-search').value,
      minPrice: document.getElementById('f-min').value,
      maxPrice: document.getElementById('f-max').value,
      sort: document.getElementById('f-sort').value,
    };
    if (Products.loadFailed()) {
      document.getElementById('results-count').textContent = '';
      document.getElementById('products-list').innerHTML = Products.errorHTML();
      return;
    }
    const filtered = Products.filterAndSort(ALL_PRODUCTS, filters);
    document.getElementById('results-count').textContent = `${filtered.length} منتج`;
    document.getElementById('products-list').innerHTML = filtered.length
      ? filtered.map(Products.productCardHTML).join('')
      : `<div class="empty-state" style="grid-column:1/-1;"><div class="icon">🔍</div><h3>لا توجد نتائج</h3><p>جرّب تعديل الفلاتر أو كلمة البحث.</p></div>`;
  }

  document.addEventListener('DOMContentLoaded', async () => {
    renderHeader('products');
    renderFooter();

    const catSelect = document.getElementById('f-category');
    const [categories, products] = await Promise.all([Products.loadCategories(), Products.loadAll()]);
    categories.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.slug; opt.textContent = c.name;
      catSelect.appendChild(opt);
    });

    ALL_PRODUCTS = products;

    const params = getQueryParams();
    if (params.category) catSelect.value = params.category;
    if (params.q) document.getElementById('f-search').value = params.q;

    renderList();

    ['f-search', 'f-category', 'f-min', 'f-max', 'f-sort'].forEach(id => {
      document.getElementById(id).addEventListener('input', renderList);
      document.getElementById(id).addEventListener('change', renderList);
    });
  });
</script>
@endpush
