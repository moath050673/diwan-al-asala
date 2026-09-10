<?php $__env->startSection('title', 'التصنيفات | متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-header">
  <div class="container"><h1>التصنيفات</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / التصنيفات</div></div>
</div>
<div class="section"><div class="container"><div class="categories-grid" id="categories-grid"></div></div></div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    renderHeader('categories');
    renderFooter();
    document.getElementById('categories-grid').innerHTML = Products.categories.map(c => `
      <div class="category-card">
        <div class="cat-img">${c.icon}</div>
        <h3>${c.name}</h3>
        <a class="view-link" href="/products?category=${c.slug}">عرض المنتجات ←</a>
      </div>
    `).join('');
  });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/pages/categories.blade.php ENDPATH**/ ?>