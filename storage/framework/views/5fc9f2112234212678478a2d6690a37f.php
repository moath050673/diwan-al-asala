<?php $__env->startSection('title', 'تم استلام طلبك | متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="section">
  <div class="container" style="max-width:600px;">
    <div class="order-success-card" id="order-success-card"></div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    renderHeader('');
    renderFooter();

    const card = document.getElementById('order-success-card');
    let order;
    try { order = JSON.parse(localStorage.getItem('diwan_last_order')); } catch { order = null; }

    if (!order) {
      card.innerHTML = `<div class="empty-state"><div class="icon">🧾</div><h3>لا يوجد طلب حديث</h3><a href="/products" class="btn btn-primary" style="margin-top:16px;">تصفح المنتجات</a></div>`;
      return;
    }

    card.innerHTML = `
      <div class="check">✔</div>
      <h2 style="font-family:var(--font-display); color:var(--primary);">تم استلام طلبك بنجاح</h2>
      <p style="color:var(--muted); margin-top:8px;">سيتواصل معك فريق متجر ديوان الأصالة قريبًا لتأكيد الطلب.</p>
      <div class="order-info-list">
        <div class="row"><span>رقم الطلب</span><strong>#${order.orderNumber}</strong></div>
        <div class="row"><span>اسم العميل</span><strong>${order.customerName}</strong></div>
        <div class="row"><span>الإجمالي</span><strong>${Products.formatPrice(order.total)}</strong></div>
        <div class="row"><span>طريقة الدفع</span><strong>${order.paymentMethodLabel}</strong></div>
        <div class="row"><span>حالة الطلب</span><strong>جديد</strong></div>
      </div>
      <a href="${WhatsAppLink.orderConfirmation(order)}" target="_blank" rel="noopener" class="btn btn-whatsapp">تواصل معنا عبر واتساب</a>
      <div style="margin-top:14px;"><a href="/products" class="btn btn-outline">متابعة التسوق</a></div>
    `;
  });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/pages/order-success.blade.php ENDPATH**/ ?>