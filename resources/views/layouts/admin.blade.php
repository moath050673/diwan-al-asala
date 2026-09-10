<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'لوحة تحكم متجر ديوان الأصالة')</title>
<link rel="stylesheet" href="/admin-assets/css/admin.css">
</head>
<body>
@yield('content')
<script>window.DIWAN_API_BASE = '/api';</script>
<script src="/admin-assets/js/admin.js"></script>
@stack('scripts')
</body>
</html>
