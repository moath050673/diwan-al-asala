<?php $__env->startSection('title', 'الإعدادات | لوحة تحكم متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>إعدادات المتجر</h1></div>
    <div class="card">
      <form id="settings-form">
        <div class="form-row"><label>اسم المتجر</label><input type="text" name="store_name"></div>
        <div class="form-row"><label>رقم واتساب المتجر</label><input type="text" name="whatsapp_number"></div>
        <div class="form-row"><label>رابط فيسبوك</label><input type="text" name="facebook_url"></div>
        <div class="form-row"><label>رابط انستقرام</label><input type="text" name="instagram_url"></div>
        <div class="form-row"><label>تكلفة التوصيل (صنعاء)</label><input type="number" name="shipping_cost_sanaa"></div>
        <hr style="margin:18px 0; border-color:var(--cream);">
        <div class="form-row"><label>اسم حساب جيب</label><input type="text" name="jib_account_name"></div>
        <div class="form-row"><label>رقم حساب جيب</label><input type="text" name="jib_account_number"></div>
        <div class="form-row"><label>اسم حساب كريمي</label><input type="text" name="kareemi_account_name"></div>
        <div class="form-row"><label>رقم حساب كريمي</label><input type="text" name="kareemi_account_number"></div>
        <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
      </form>
    </div>
  </main>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
  requireAdminAuth();
  renderSidebar('settings');
  const form = document.getElementById('settings-form');
  (async () => {
    const res = await adminRequest('/settings/all');
    if (res && res.data) Object.entries(res.data).forEach(([k, v]) => { const input = form.elements[k]; if (input) input.value = v; });
  })();
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    await adminRequest('/settings', { method: 'PUT', body: JSON.stringify(Object.fromEntries(fd.entries())) });
    alert('تم حفظ الإعدادات بنجاح');
  });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/admin/settings.blade.php ENDPATH**/ ?>