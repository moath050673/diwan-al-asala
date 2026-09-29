@extends('layouts.app')

@section('title', 'متجر ديوان الأصالة | عطور وبخور وزباد بروح الأصالة اليمنية')

@push('head')
<link rel="stylesheet" href="/css/home.css">
@endpush

@push('structured-data')
<script type="application/ld+json">{!! json_encode($storeSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endpush

@section('content')
{{-- ========== الواجهة الرئيسية: مشهد ثلاثي الأبعاد + دخان بخور متصاعد ========== --}}
<section class="landing-hero" id="landing-hero">
  <canvas class="hero-scene" id="hero-scene" aria-hidden="true"></canvas>
  <div class="hero-vignette" aria-hidden="true"></div>

  <div class="container landing-hero-inner">
    <div class="landing-hero-copy">
      <span class="landing-eyebrow">متجر ديوان الأصالة · صنعاء</span>
      <h1>عبقٌ يمنيٌّ أصيل<br><span>يملأ مجلسك</span></h1>
      <p>بخور معدّ يدويًا، زباد طبيعي، وعطور شرقية مختارة بعناية. نوصلها إلى باب بيتك في صنعاء، وتدفع عند الاستلام.</p>
      <div class="hero-actions">
        <a href="/products" class="btn btn-primary btn-lg">تسوّق الآن</a>
        <a href="#" id="hero-wa-btn" class="btn btn-ghost btn-lg" target="_blank" rel="noopener">
          <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.87.5 3.6 1.4 5.1L2 22l5.15-1.5a9.9 9.9 0 0 0 4.9 1.28c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm0 18.05c-1.6 0-3.1-.44-4.4-1.2l-.31-.18-3.06.9.9-2.98-.2-.32a8.16 8.16 0 0 1-1.24-4.36c0-4.53 3.7-8.23 8.3-8.23 4.6 0 8.3 3.7 8.3 8.23 0 4.53-3.7 8.14-8.3 8.14Z"/></svg>
          اسألنا عبر واتساب
        </a>
      </div>
      <ul class="hero-facts">
        <li>توصيل داخل صنعاء</li>
        <li>الدفع عند الاستلام</li>
        <li>جيب وكريمي</li>
      </ul>
    </div>

    <div class="landing-hero-visual">
      <div class="mabkhara-glow" aria-hidden="true"></div>
      <svg class="mabkhara-illustration" id="hero-mabkhara" viewBox="0 0 240 400" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="مبخرة يتصاعد منها البخور">
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

  <a href="#landing-categories" class="scroll-cue" aria-label="انتقل إلى التصنيفات"><span></span></a>
</section>

{{-- ========== لماذا ديوان الأصالة ========== --}}
<section class="trust-strip" aria-label="مزايا الطلب من المتجر">
  <div class="container trust-grid">
    <div class="trust-item">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17.5" cy="17.5" r="1.8"/></svg>
      <div><strong>توصيل داخل صنعاء</strong><span id="trust-shipping">تكلفة توصيل ثابتة</span></div>
    </div>
    <div class="trust-item">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="18" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9.5v5M18 9.5v5"/></svg>
      <div><strong>الدفع عند الاستلام</strong><span>افحص طلبك ثم ادفع</span></div>
    </div>
    <div class="trust-item">
      <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="6" y="2.5" width="12" height="19" rx="2.5"/><path d="M10 18h4"/></svg>
      <div><strong>جيب وكريمي</strong><span>تحويل سريع وآمن</span></div>
    </div>
    <div class="trust-item">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3c-1 3-4 4.5-4 8.5a4 4 0 0 0 8 0C16 7.5 13 6 12 3Z"/><path d="M12 15.5v5.5"/></svg>
      <div><strong>منتجات أصيلة</strong><span>مختارة ومجرّبة بعناية</span></div>
    </div>
  </div>
</section>

{{-- ========== التصنيفات (من قاعدة البيانات) ========== --}}
<section class="section" id="landing-categories">
  <div class="container">
    <div class="section-title">
      <span class="eyebrow">تصفّح حسب</span>
      <h2>التصنيفات</h2>
      <p>كل تشكيلتنا في مكان واحد، من البخور والزباد إلى ما يُضاف حديثًا للمتجر</p>
    </div>
    <div class="categories-grid" id="categories-grid"></div>
  </div>
</section>

{{-- ========== المنتجات المميزة ========== --}}
<section class="section section-raised">
  <div class="container">
    <div class="section-title">
      <span class="eyebrow">الأكثر تميزًا</span>
      <h2>منتجات مميزة</h2>
      <p>مختارات متجر ديوان الأصالة من أفخم المنتجات</p>
    </div>
    <div class="products-grid" id="featured-products"></div>
    <div class="section-cta"><a href="/products" class="btn btn-outline">عرض كل المنتجات</a></div>
  </div>
</section>

{{-- ========== خطوات الطلب (تسلسل فعلي) ========== --}}
<section class="section">
  <div class="container">
    <div class="section-title">
      <span class="eyebrow">طلبك في دقائق</span>
      <h2>كيف تطلب؟</h2>
    </div>
    <ol class="order-steps">
      <li><span class="step-num">١</span><h3>اختر منتجاتك</h3><p>أضف ما يعجبك إلى السلة من المتجر.</p></li>
      <li><span class="step-num">٢</span><h3>أدخل عنوانك</h3><p>الاسم ورقم الجوال والعنوان في صنعاء.</p></li>
      <li><span class="step-num">٣</span><h3>اختر طريقة الدفع</h3><p>عند الاستلام، أو تحويل عبر جيب أو كريمي.</p></li>
      <li><span class="step-num">٤</span><h3>نوصل لباب بيتك</h3><p>نتواصل معك لتأكيد الطلب ثم نوصله.</p></li>
    </ol>
  </div>
</section>

{{-- ========== دعوة أخيرة ========== --}}
<section class="final-cta">
  <div class="container final-cta-inner">
    <div>
      <h2>محتار في الاختيار؟</h2>
      <p>راسلنا على واتساب وسنساعدك تختار البخور أو العطر المناسب لك أو لهديتك.</p>
    </div>
    <a href="#" id="final-wa-btn" class="btn btn-whatsapp btn-lg" target="_blank" rel="noopener">تواصل معنا عبر واتساب</a>
  </div>
</section>
@endsection

@push('scripts')
<script src="/js/vendor/three.min.js" defer></script>
<script src="/js/hero-scene.js" defer></script>
<script>
  document.addEventListener('DOMContentLoaded', async () => {
    renderHeader('home');
    renderFooter();
    document.getElementById('hero-wa-btn').href = WhatsAppLink.general();
    document.getElementById('final-wa-btn').href = WhatsAppLink.general();

    const shipping = window.DIWAN_SETTINGS.shippingCost;
    if (shipping > 0) document.getElementById('trust-shipping').textContent = 'التوصيل بـ ' + Products.formatPrice(shipping);

    const categories = await Products.loadCategories();
    document.getElementById('categories-grid').innerHTML = categories.map(Products.categoryCardHTML).join('');

    const all = await Products.loadAll();
    const featured = all.filter(p => p.featured);
    document.getElementById('featured-products').innerHTML =
      featured.map(Products.productCardHTML).join('') || '<p>لا توجد منتجات مميزة حاليًا.</p>';
  });
</script>
@endpush
