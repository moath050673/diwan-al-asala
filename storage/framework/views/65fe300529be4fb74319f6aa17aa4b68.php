<?php $__env->startSection('title', 'العملاء | لوحة تحكم متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>العملاء</h1></div>
    <div class="card">
      <table class="admin-table">
        <thead><tr><th>الاسم</th><th>الهاتف</th><th>المدينة</th><th>عدد الطلبات</th><th>إجمالي المشتريات</th></tr></thead>
        <tbody id="customers-tbody"></tbody>
      </table>
    </div>
  </main>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
  requireAdminAuth();
  renderSidebar('customers');
  (async () => {
    const res = await adminRequest('/customers?limit=100');
    const rows = (res && res.data) || [];
    document.getElementById('customers-tbody').innerHTML = rows.map(c => `
      <tr><td>${c.name}</td><td>${c.phone}</td><td>${c.city || '—'}</td><td>${c.ordersCount}</td><td>${formatPrice(c.lifetimeValue)}</td></tr>
    `).join('') || '<tr><td colspan="5">لا يوجد عملاء بعد.</td></tr>';
  })();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/admin/customers.blade.php ENDPATH**/ ?>