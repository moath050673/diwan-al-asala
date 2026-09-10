@extends('layouts.app')
@section('title', 'سياسة الخصوصية | متجر ديوان الأصالة')
@section('content')
<div class="page-header"><div class="container"><h1>سياسة الخصوصية</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / سياسة الخصوصية</div></div></div>
<div class="section"><div class="container" style="max-width:820px; background:var(--white); border-radius:var(--radius-lg); padding:36px; box-shadow:var(--shadow-sm); line-height:2;">
  <p>نحرص في متجر ديوان الأصالة على حماية خصوصية بيانات عملائنا. تُستخدم البيانات التي تقدمها (الاسم، الهاتف، العنوان) فقط لغرض معالجة طلبك والتواصل معك بخصوصه.</p>
  <p style="margin-top:14px;">لا تتم مشاركة بياناتك مع أي جهة خارجية دون موافقتك، باستثناء ما يلزم لإتمام عملية التوصيل.</p>
  <p style="margin-top:14px;">لأي استفسار بخصوص بياناتك يمكنك التواصل معنا عبر واتساب من صفحة "تواصل معنا".</p>
</div></div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded', () => { renderHeader(''); renderFooter(); });</script>
@endpush
