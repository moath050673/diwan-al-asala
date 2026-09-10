@extends('layouts.app')

@section('title', 'تفاصيل المنتج | متجر ديوان الأصالة')

@section('content')
<div class="page-header">
  <div class="container">
    <h1 id="crumb-name">تفاصيل المنتج</h1>
    <div class="breadcrumb"><a href="/">الرئيسية</a> / <a href="/products">المنتجات</a> / <span id="crumb-current"></span></div>
  </div>
</div>

<div class="section">
  <div class="container" id="product-detail-wrap"></div>
  <div class="container" style="margin-top:60px; display:none;" id="related-wrap">
    <div class="section-title"><span class="eyebrow">قد يعجبك أيضًا</span><h2>منتجات مشابهة</h2></div>
    <div class="products-grid" id="related-products"></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // معرّف المنتج يأتي من رابط Laravel النظيف: /product/{id}
  window.DIWAN_PRODUCT_ID = "{{ $id }}";
</script>
<script>
  document.addEventListener('DOMContentLoaded', async () => {
    renderHeader('products');
    renderFooter();

    const id = window.DIWAN_PRODUCT_ID;
    const product = await Products.getById(id);
    const wrap = document.getElementById('product-detail-wrap');

    if (!product) {
      wrap.innerHTML = `<div class="empty-state"><div class="icon">😕</div><h3>المنتج غير موجود</h3><a href="/products" class="btn btn-primary" style="margin-top:16px;">تصفح المنتجات</a></div>`;
      return;
    }

    document.title = product.name + ' | متجر ديوان الأصالة';
    document.getElementById('crumb-name').textContent = product.name;
    document.getElementById('crumb-current').textContent = product.name;

    const outOfStock = product.stock <= 0;
    const discount = product.oldPrice ? Math.round(100 - (product.price / product.oldPrice) * 100) : null;

    wrap.innerHTML = `
      <div class="pd-wrap">
        <div>
          <div class="pd-gallery-main">🧴</div>
          <div class="pd-thumbs"><div class="thumb active">🧴</div><div class="thumb">🧴</div><div class="thumb">🧴</div></div>
        </div>
        <div class="pd-info">
          <span class="product-cat">${product.categoryName}</span>
          <h1>${product.name}</h1>
          <span class="product-rating">${'★'.repeat(Math.round(product.rating))}${'☆'.repeat(5 - Math.round(product.rating))} (${product.rating})</span>
          <div class="pd-price-row">
            <span class="now">${Products.formatPrice(product.price)}</span>
            ${product.oldPrice ? `<span class="old">${Products.formatPrice(product.oldPrice)}</span>` : ''}
            ${discount ? `<span class="badge" style="position:static;">خصم ${discount}%</span>` : ''}
          </div>
          <div class="pd-meta">
            <span>SKU: ${product.sku}</span>
            <span class="stock-note ${outOfStock ? 'out' : ''}">${outOfStock ? 'نفد من المخزون' : `متوفر (${product.stock} قطعة)`}</span>
          </div>
          <p class="pd-desc">${product.desc}</p>
          <div class="pd-qty-row">
            <span>الكمية:</span>
            <div class="qty-control">
              <button id="qty-minus">−</button>
              <input type="number" id="qty-input" value="1" min="1" max="${product.stock || 1}">
              <button id="qty-plus">+</button>
            </div>
          </div>
          <div class="pd-actions">
            <button class="btn btn-primary" id="add-to-cart-btn" ${outOfStock ? 'disabled' : ''}>أضف إلى السلة</button>
            <button class="btn btn-dark" id="buy-now-btn" ${outOfStock ? 'disabled' : ''}>اشترِ الآن</button>
            <a href="#" class="btn btn-whatsapp" id="wa-inquire-btn" target="_blank" rel="noopener">اطلب عبر واتساب</a>
          </div>
        </div>
      </div>
    `;

    document.getElementById('wa-inquire-btn').href = WhatsAppLink.productInquiry(product);

    const qtyInput = document.getElementById('qty-input');
    document.getElementById('qty-minus').addEventListener('click', () => { qtyInput.value = Math.max(1, +qtyInput.value - 1); });
    document.getElementById('qty-plus').addEventListener('click', () => { qtyInput.value = Math.min(product.stock, +qtyInput.value + 1); });

    document.getElementById('add-to-cart-btn').addEventListener('click', () => {
      Cart.add(product, +qtyInput.value);
      showToast(`تمت إضافة "${product.name}" إلى السلة`, 'success');
    });
    document.getElementById('buy-now-btn').addEventListener('click', () => {
      Cart.add(product, +qtyInput.value);
      window.location.href = '/checkout';
    });

    const all = await Products.loadAll();
    const related = all.filter(p => p.category === product.category && p.id !== product.id).slice(0, 4);
    if (related.length) {
      document.getElementById('related-wrap').style.display = 'block';
      document.getElementById('related-products').innerHTML = related.map(Products.productCardHTML).join('');
    }
  });
</script>
@endpush
