<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="/favicon.ico" sizes="48x48">
<link rel="icon" type="image/png" href="/img/icon-192.png" sizes="192x192">
{{-- تثبيت اللوحة كتطبيق على الجوال — شرط لإشعارات الآيفون (إضافة إلى الشاشة الرئيسية) --}}
<link rel="manifest" href="/admin-assets/admin.webmanifest">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="إدارة الديوان">
<meta name="theme-color" content="#2E2318">
<title>@yield('title', 'لوحة تحكم متجر ديوان الأصالة')</title>
<link rel="stylesheet" href="{{ \App\Support\Asset::url('/admin-assets/css/admin.css') }}">
</head>
<body>
@yield('content')
<script>window.DIWAN_API_BASE = '/api';</script>
<script src="{{ \App\Support\Asset::url('/admin-assets/js/admin.js') }}"></script>
@stack('scripts')
</body>
</html>
