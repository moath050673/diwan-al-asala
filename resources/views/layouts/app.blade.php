<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@php
  // بيانات SEO لكل صفحة: كل صفحة تحدد title/description، وصفحة المنتج تحدد og_image و og_type
  $seoTitle = trim($__env->yieldContent('title', 'متجر ديوان الأصالة | عطور وبخور وزباد'));
  $seoDescription = trim($__env->yieldContent('description', 'متجر ديوان الأصالة، متخصص في بيع الزباد والبخور والعطور الأصيلة، توصيل داخل صنعاء اليمن.'));
  $seoImage = trim($__env->yieldContent('og_image')) ?: url('/img/logo.png');
  $seoUrl = url()->current();
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="robots" content="@yield('robots', 'index, follow')">
<link rel="canonical" href="{{ $seoUrl }}">
<link rel="icon" type="image/png" href="/img/favicon.png">
<meta name="theme-color" content="#2E2318">
{{-- مشاركة الروابط على واتساب/فيسبوك/تويتر (Open Graph + Twitter Cards) --}}
<meta property="og:site_name" content="متجر ديوان الأصالة">
<meta property="og:locale" content="ar_YE">
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
@stack('structured-data')
{{-- الخطوط المعرّفة في style.css (--font-display / --font-body) — لم تكن تُحمَّل من قبل --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- تحميل غير حاجب: الصفحة تظهر فورًا بخط احتياطي ثم تتبدل للخطوط (display=swap) — ملف Google
     كان يؤخر أول ظهور للصفحة حتى يكتمل تحميله --}}
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@400;500;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@400;500;700;800&display=swap"></noscript>
<link rel="stylesheet" href="{{ \App\Support\Asset::url('/css/style.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('/css/components.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::url('/css/responsive.css') }}">
@stack('head')
</head>
<body>
{{-- لمستخدمي لوحة المفاتيح وقارئات الشاشة: يظهر فقط عند التركيز عليه بزر Tab --}}
<a href="#main-content" class="skip-link">تخطَّ إلى المحتوى</a>

<header id="site-header"></header>

<main id="main-content" tabindex="-1">
@yield('content')
</main>

<footer id="site-footer"></footer>
<a href="#" class="whatsapp-float" id="whatsapp-float" aria-label="تواصل عبر واتساب">
  <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.87.5 3.6 1.4 5.1L2 22l5.15-1.5a9.9 9.9 0 0 0 4.9 1.28c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm0 18.05c-1.6 0-3.1-.44-4.4-1.2l-.31-.18-3.06.9.9-2.98-.2-.32a8.16 8.16 0 0 1-1.24-4.36c0-4.53 3.7-8.23 8.3-8.23 4.6 0 8.3 3.7 8.3 8.23 0 4.53-3.7 8.14-8.3 8.14Z"/></svg>
</a>

<script>
  // عنوان الـ API الأساسي — يبقى نفس نطاق Laravel (نفس الدومين) دائمًا
  window.DIWAN_API_BASE = '/api';
  // إعدادات المتجر من قاعدة البيانات (لوحة التحكم ← الإعدادات)
  window.DIWAN_SERVER_SETTINGS = @json($publicSettings ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
</script>
<script src="{{ \App\Support\Asset::url('/js/api.js') }}"></script>
<script src="{{ \App\Support\Asset::url('/js/products.js') }}"></script>
<script src="{{ \App\Support\Asset::url('/js/cart.js') }}"></script>
<script src="{{ \App\Support\Asset::url('/js/whatsapp.js') }}"></script>
<script src="{{ \App\Support\Asset::url('/js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
