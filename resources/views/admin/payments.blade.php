@extends('layouts.admin')
@section('title', 'الدفعات | لوحة تحكم متجر ديوان الأصالة')
@section('content')
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>مراجعة الدفعات اليدوية</h1></div>
    <div class="card">
      <p style="color:#8A7A68; margin-bottom:16px;">راجع الطلبات المدفوعة عبر جيب أو كريمي من صفحة <a href="/admin/orders" style="color:var(--gold); font-weight:700;">الطلبات</a>، وافتح كل طلب لعرض رقم العملية وإيصال الدفع وتغيير حالته إلى "مدفوع" أو "مرفوض".</p>
      <p style="color:#8A7A68;">الدفع عند الاستلام لا يحتاج إلى مراجعة يدوية.</p>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>requireAdminAuth(); renderSidebar('payments');</script>
@endpush
