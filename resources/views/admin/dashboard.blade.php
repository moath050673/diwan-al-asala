@extends('layouts.admin')
@section('title', 'لوحة القيادة | متجر ديوان الأصالة')
@section('content')
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>لوحة القيادة</h1></div>
    <div class="stat-grid" id="stat-grid"></div>
    <div class="card">
      <h3 style="margin-bottom:14px; color:var(--primary);">المبيعات خلال آخر 14 يوم</h3>
      <div id="sales-chart" style="display:flex; align-items:flex-end; gap:6px; height:160px;"></div>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
  requireAdminAuth();
  renderSidebar('dashboard');

  (async () => {
    const res = await adminRequest('/dashboard/stats');
    if (!res || !res.success) return;
    const d = res.data;
    document.getElementById('stat-grid').innerHTML = `
      <a class="stat-card" href="/admin/orders"><div class="label">إجمالي المبيعات</div><div class="value">${formatPrice(d.totalSales)}</div></a>
      <a class="stat-card" href="/admin/orders"><div class="label">طلبات اليوم</div><div class="value">${d.todayOrders}</div></a>
      <a class="stat-card" href="/admin/orders"><div class="label">الطلبات الجديدة</div><div class="value">${d.newOrders}</div></a>
      <a class="stat-card" href="/admin/orders"><div class="label">قيد التجهيز</div><div class="value">${d.processingOrders}</div></a>
      <a class="stat-card" href="/admin/orders"><div class="label">الطلبات المكتملة</div><div class="value">${d.deliveredOrders}</div></a>
      <a class="stat-card" href="/admin/products"><div class="label">عدد المنتجات</div><div class="value">${d.productsCount}</div></a>
      <a class="stat-card" href="/admin/products"><div class="label">منخفضة المخزون</div><div class="value">${d.lowStockCount}</div></a>
      <a class="stat-card" href="/admin/customers"><div class="label">عدد العملاء</div><div class="value">${d.customersCount}</div></a>
    `;
    const maxVal = Math.max(1, ...d.salesByDay.map(r => Number(r.total)));
    document.getElementById('sales-chart').innerHTML = d.salesByDay.map(r => `
      <div title="${escapeHtml(r.date)}: ${formatPrice(r.total)}" style="flex:1; background:var(--gold); border-radius:4px 4px 0 0; height:${(r.total / maxVal) * 100}%; min-height:4px;"></div>
    `).join('') || '<p style="color:#8A7A68;">لا توجد بيانات مبيعات بعد.</p>';
  })();
</script>
@endpush
