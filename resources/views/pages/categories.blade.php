@extends('layouts.app')
@section('title', 'التصنيفات | متجر ديوان الأصالة')
@section('description', 'تصفح تصنيفات متجر ديوان الأصالة: الزباد، البخور، العطور، الميداليات وغيرها من المنتجات اليمنية الأصيلة.')
@section('content')
<div class="page-header">
  <div class="container"><h1>التصنيفات</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / التصنيفات</div></div>
</div>
<div class="section"><div class="container"><div class="categories-grid" id="categories-grid"></div></div></div>
@endsection
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', async () => {
    renderHeader('categories');
    renderFooter();
    const categories = await Products.loadCategories();
    document.getElementById('categories-grid').innerHTML = Products.loadFailed()
      ? Products.errorHTML()
      : categories.map(Products.categoryCardHTML).join('');
  });
</script>
@endpush
