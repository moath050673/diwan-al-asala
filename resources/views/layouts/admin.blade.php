<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/png" href="/img/favicon.png">
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
