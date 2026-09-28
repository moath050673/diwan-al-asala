<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'متجر ديوان الأصالة | عطور وبخور وزباد')</title>
<meta name="description" content="@yield('description', 'متجر ديوان الأصالة، متخصص في بيع الزباد والبخور والعطور الأصيلة، توصيل داخل صنعاء اليمن.')">
<link rel="canonical" href="{{ url()->current() }}">
<link rel="icon" href="/img/logo.png">
{{-- الخطوط المعرّفة في style.css (--font-display / --font-body) — لم تكن تُحمَّل من قبل --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Aref+Ruqaa:wght@400;700&family=Tajawal:wght@400;500;700;800&display=swap">
<link rel="stylesheet" href="/css/style.css">
<link rel="stylesheet" href="/css/components.css">
<link rel="stylesheet" href="/css/responsive.css">
@stack('head')
</head>
<body>

<header id="site-header"></header>

@yield('content')

<footer id="site-footer"></footer>
<a href="#" class="whatsapp-float" id="whatsapp-float" aria-label="تواصل عبر واتساب">
  <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.87.5 3.6 1.4 5.1L2 22l5.15-1.5a9.9 9.9 0 0 0 4.9 1.28c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2Zm0 18.05c-1.6 0-3.1-.44-4.4-1.2l-.31-.18-3.06.9.9-2.98-.2-.32a8.16 8.16 0 0 1-1.24-4.36c0-4.53 3.7-8.23 8.3-8.23 4.6 0 8.3 3.7 8.3 8.23 0 4.53-3.7 8.14-8.3 8.14Z"/></svg>
</a>

<script>
  // عنوان الـ API الأساسي — يبقى نفس نطاق Laravel (نفس الدومين) دائمًا
  window.DIWAN_API_BASE = '/api';
  // إعدادات المتجر من قاعدة البيانات (لوحة التحكم ← الإعدادات)
  window.DIWAN_SERVER_SETTINGS = @json($publicSettings ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
</script>
<script src="/js/api.js"></script>
<script src="/js/products.js"></script>
<script src="/js/cart.js"></script>
<script src="/js/whatsapp.js"></script>
<script src="/js/app.js"></script>
@stack('scripts')
</body>
</html>
