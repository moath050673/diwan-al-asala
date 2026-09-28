@extends('layouts.admin')
@section('title', 'العملاء | لوحة تحكم متجر ديوان الأصالة')
@section('content')
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>العملاء</h1></div>
    <div class="card">
      <table class="admin-table responsive">
        <thead><tr><th>الاسم</th><th>الهاتف</th><th>المدينة</th><th>عدد الطلبات</th><th>إجمالي المشتريات</th></tr></thead>
        <tbody id="customers-tbody"></tbody>
      </table>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
  requireAdminAuth();
  renderSidebar('customers');
  (async () => {
    const res = await adminRequest('/customers?limit=100');
    const rows = (res && res.data) || [];
    document.getElementById('customers-tbody').innerHTML = rows.map(c => `
      <tr><td data-label="الاسم">${escapeHtml(c.name)}</td><td data-label="الهاتف">${escapeHtml(c.phone)}</td><td data-label="المدينة">${escapeHtml(c.city || '—')}</td><td data-label="الطلبات">${escapeHtml(c.ordersCount)}</td><td data-label="المشتريات">${formatPrice(c.lifetimeValue)}</td></tr>
    `).join('') || '<tr><td colspan="5">لا يوجد عملاء بعد.</td></tr>';
  })();
</script>
@endpush
