@extends('layouts.app')

@section('title', $product->name.' | متجر ديوان الأصالة')
@section('description', $seoDescription)
@section('og_type', 'product')
@if ($seoImage)
  @section('og_image', $seoImage)
@endif

@push('structured-data')
<script type="application/ld+json">{!! json_encode($productSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('content')
<div class="page-header">
  <div class="container">
    {{-- عنوان الصفحة الرئيسي (h1) هو اسم المنتج داخل التفاصيل — هنا عنوان مرئي فقط بنفس الشكل --}}
    <div class="page-title" id="crumb-name">{{ $product->name }}</div>
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
  window.DIWAN_PRODUCT_ID = @json((int) $id);
</script>
<script>
  /* ---------- معرض صور المنتج: سحب بالإصبع على الجوال، أسهم ومصغّرات على الكمبيوتر ---------- */
  function galleryHTML(product) {
    const images = (product.images && product.images.length) ? product.images : (product.image ? [product.image] : []);
    if (!images.length) {
      return `<div class="pd-gallery"><div class="pd-slide pd-slide-empty">${Products.placeholderHTML(product.category)}</div></div>`;
    }
    const alt = escapeHtml(product.name);
    const many = images.length > 1;
    return `
      <div class="pd-gallery" id="pd-gallery">
        <div class="pd-stage">
          <div class="pd-track" id="pd-track" tabindex="0" aria-label="صور المنتج — اسحب للتقليب">
            ${images.map((src, i) => `
              <div class="pd-slide" data-index="${i}">
                <img src="${escapeHtml(src)}" alt="${alt} — صورة ${i + 1}" ${i ? 'loading="lazy"' : ''} draggable="false">
              </div>`).join('')}
          </div>
          ${many ? `
            <button class="pd-nav pd-prev" type="button" aria-label="الصورة السابقة">›</button>
            <button class="pd-nav pd-next" type="button" aria-label="الصورة التالية">‹</button>
            <span class="pd-counter" id="pd-counter">1 / ${images.length}</span>` : ''}
        </div>
        ${many ? `
          <div class="pd-thumbs" id="pd-thumbs">
            ${images.map((src, i) => `
              <button class="thumb ${i === 0 ? 'active' : ''}" type="button" data-index="${i}" aria-label="عرض الصورة ${i + 1}">
                <img src="${escapeHtml(src)}" alt="" loading="lazy">
              </button>`).join('')}
          </div>` : ''}
      </div>`;
  }

  function initGallery() {
    const track = document.getElementById('pd-track');
    if (!track) return;
    const slides = [...track.querySelectorAll('.pd-slide')];
    const thumbs = [...document.querySelectorAll('#pd-thumbs .thumb')];
    const counter = document.getElementById('pd-counter');
    let current = 0;

    const goTo = (i) => {
      current = (i + slides.length) % slides.length;
      // scrollIntoView يتعامل مع اتجاه RTL بشكل صحيح في كل المتصفحات، و nearest يمنع قفز الصفحة عموديًا
      slides[current].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'start' });
    };

    const setActive = (i) => {
      current = i;
      thumbs.forEach((t, k) => t.classList.toggle('active', k === i));
      thumbs[i]?.scrollIntoView({ block: 'nearest', inline: 'nearest' });
      if (counter) counter.textContent = `${i + 1} / ${slides.length}`;
    };

    // تحديد الصورة الظاهرة بعد السحب بالإصبع
    const io = new IntersectionObserver((entries) => {
      entries.forEach(en => { if (en.isIntersecting) setActive(Number(en.target.dataset.index)); });
    }, { root: track, threshold: 0.6 });
    slides.forEach(s => io.observe(s));

    thumbs.forEach(t => t.addEventListener('click', () => goTo(Number(t.dataset.index))));
    document.querySelector('.pd-prev')?.addEventListener('click', () => goTo(current - 1));
    document.querySelector('.pd-next')?.addEventListener('click', () => goTo(current + 1));
    track.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') goTo(current + 1);   // RTL: اليسار = التالي
      if (e.key === 'ArrowRight') goTo(current - 1);
    });
  }

  document.addEventListener('DOMContentLoaded', async () => {
    renderHeader('products');
    renderFooter();

    const id = window.DIWAN_PRODUCT_ID;
    const product = await Products.getDetails(id);
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
        ${galleryHTML(product)}
        <div class="pd-info">
          <span class="product-cat">${escapeHtml(product.categoryName)}</span>
          <h1>${escapeHtml(product.name)}</h1>
          <span class="product-rating">${Products.ratingHTML(product.rating)}</span>
          <div class="pd-price-row">
            <span class="now">${Products.formatPrice(product.price)}</span>
            ${product.oldPrice ? `<span class="old">${Products.formatPrice(product.oldPrice)}</span>` : ''}
            ${discount ? `<span class="badge" style="position:static;">خصم ${discount}%</span>` : ''}
          </div>
          <div class="pd-meta">
            <span>SKU: ${escapeHtml(product.sku)}</span>
            <span class="stock-note ${outOfStock ? 'out' : ''}">${outOfStock ? 'نفد من المخزون' : `متوفر (${product.stock} قطعة)`}</span>
          </div>
          <p class="pd-desc">${escapeHtml(product.desc || "")}</p>
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

    initGallery();
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
