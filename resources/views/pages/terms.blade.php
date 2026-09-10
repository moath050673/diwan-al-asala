@extends('layouts.app')
@section('title', 'الشروط والأحكام | متجر ديوان الأصالة')
@section('content')
<div class="page-header"><div class="container"><h1>الشروط والأحكام</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / الشروط والأحكام</div></div></div>
<div class="section"><div class="container" style="max-width:820px; background:var(--white); border-radius:var(--radius-lg); padding:36px; box-shadow:var(--shadow-sm); line-height:2;">
  <p>باستخدامك لمتجر ديوان الأصالة فإنك توافق على الشروط التالية:</p>
  <p style="margin-top:14px;">1. الأسعار المعروضة قابلة للتغيير دون إشعار مسبق.</p>
  <p style="margin-top:10px;">2. يتم تأكيد الطلب عبر التواصل الهاتفي أو واتساب قبل الشحن.</p>
  <p style="margin-top:10px;">3. طرق الدفع المتاحة هي الدفع عند الاستلام، جيب، وكريمي، ويتم التحقق من الدفع الإلكتروني يدويًا من فريق المتجر.</p>
  <p style="margin-top:10px;">4. التوصيل متاح حاليًا داخل صنعاء، وقد تختلف تكلفته حسب المنطقة.</p>
</div></div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => { renderHeader(''); renderFooter(); });</script>
@endpush
