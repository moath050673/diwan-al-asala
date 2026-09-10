@extends('layouts.app')
@section('title', 'التصنيفات | متجر ديوان الأصالة')
@section('content')
<div class="page-header">
  <div class="container"><h1>التصنيفات</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / التصنيفات</div></div>
</div>
<div class="section"><div class="container"><div class="categories-grid" id="categories-grid"></div></div></div>
@endsection
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    renderHeader('categories');
    renderFooter();
    document.getElementById('categories-grid').innerHTML = Products.categories.map(c => `
      <div class="category-card">
        <div class="cat-img">${c.icon}</div>
        <h3>${c.name}</h3>
        <a class="view-link" href="/products?category=${c.slug}">عرض المنتجات ←</a>
      </div>
    `).join('');
  });
</script>
@endpush
