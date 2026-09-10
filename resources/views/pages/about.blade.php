@extends('layouts.app')
@section('title', 'من نحن | متجر ديوان الأصالة')
@section('description', 'تعرف على متجر ديوان الأصالة المتخصص في العطور والبخور والزباد في صنعاء، اليمن.')
@section('content')
<div class="page-header"><div class="container"><h1>من نحن</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / من نحن</div></div></div>
<div class="section">
  <div class="container" style="max-width:820px;">
    <div style="background:var(--white); border-radius:var(--radius-lg); padding:36px; box-shadow:var(--shadow-sm); line-height:2;">
      <p><strong>متجر ديوان الأصالة</strong> متخصص في العطور والبخور والزباد، ويهدف إلى تقديم منتجات ذات طابع أصيل وتجربة شراء سهلة للعملاء في صنعاء واليمن.</p>
      <p style="margin-top:16px;">نحرص في متجر ديوان الأصالة على اختيار كل منتج بعناية، من الزباد الطبيعي الفاخر إلى البخور اليمني الأصيل والعطور الشرقية المميزة، لنقدّم لعملائنا تجربة تجمع بين التراث والفخامة.</p>
      <p style="margin-top:16px;">نلتزم بالتوصيل السريع داخل صنعاء، وتوفير طرق دفع مرنة تناسب جميع عملائنا، مع تواصل مباشر وسريع عبر واتساب للإجابة على استفساراتكم في أي وقت.</p>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => { renderHeader('about'); renderFooter(); });</script>
@endpush
