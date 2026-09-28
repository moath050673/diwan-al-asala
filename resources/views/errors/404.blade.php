@extends('layouts.app')
@section('title', 'الصفحة غير موجودة | متجر ديوان الأصالة')
@section('content')
<div class="section">
  <div class="container">
    <div class="empty-state" style="padding-block:60px;">
      <div class="icon">🧭</div>
      <h3>الصفحة غير موجودة</h3>
      <p>ربما تغيّر الرابط أو حُذفت الصفحة.</p>
      <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap; margin-top:18px;">
        <a href="/" class="btn btn-primary">الصفحة الرئيسية</a>
        <a href="/products" class="btn btn-outline">تصفح المنتجات</a>
      </div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => { renderHeader(''); renderFooter(); });</script>
@endpush
