<?php $__env->startSection('title', 'التصنيفات | لوحة تحكم متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>التصنيفات</h1><button class="btn btn-primary" id="add-cat-btn">+ إضافة تصنيف</button></div>
    <div class="card" id="cat-form-card" style="display:none;">
      <form id="cat-form">
        <div class="form-row"><label>اسم التصنيف *</label><input type="text" name="name" required></div>
        <div class="form-row"><label>الوصف</label><textarea name="description" rows="2"></textarea></div>
        <button type="submit" class="btn btn-primary">حفظ</button>
      </form>
    </div>
    <div class="card">
      <table class="admin-table">
        <thead><tr><th>الاسم</th><th>الرابط (Slug)</th><th></th></tr></thead>
        <tbody id="cats-tbody"></tbody>
      </table>
    </div>
  </main>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
  requireAdminAuth();
  renderSidebar('categories');

  async function loadCats() {
    const res = await adminRequest('/categories');
    const rows = (res && res.data) || [];
    document.getElementById('cats-tbody').innerHTML = rows.map(c => `
      <tr><td>${c.name}</td><td>${c.slug}</td><td><button class="btn btn-danger btn-del-cat" data-id="${c.id}">حذف</button></td></tr>
    `).join('') || '<tr><td colspan="3">لا توجد تصنيفات بعد.</td></tr>';

    document.querySelectorAll('.btn-del-cat').forEach(b => b.addEventListener('click', async () => {
      if (!confirm('حذف هذا التصنيف؟')) return;
      await adminRequest(`/categories/${b.dataset.id}`, { method: 'DELETE' });
      loadCats();
    }));
  }

  document.getElementById('add-cat-btn').addEventListener('click', () => document.getElementById('cat-form-card').style.display = 'block');
  document.getElementById('cat-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    await adminRequest('/categories', { method: 'POST', body: JSON.stringify({ name: fd.get('name'), description: fd.get('description') }) });
    e.target.reset();
    document.getElementById('cat-form-card').style.display = 'none';
    loadCats();
  });

  loadCats();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/admin/categories.blade.php ENDPATH**/ ?>