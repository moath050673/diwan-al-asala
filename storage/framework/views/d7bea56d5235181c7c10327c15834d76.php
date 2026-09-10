<?php $__env->startSection('title', 'الطلبات | لوحة تحكم متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>الطلبات</h1></div>
    <div class="card">
      <table class="admin-table">
        <thead><tr><th>رقم الطلب</th><th>العميل</th><th>الهاتف</th><th>الإجمالي</th><th>الدفع</th><th>حالة الطلب</th><th>التاريخ</th><th></th></tr></thead>
        <tbody id="orders-tbody"></tbody>
      </table>
    </div>
    <div class="card" id="order-detail-card" style="display:none;">
      <h3 style="margin-bottom:14px; color:var(--primary);">تفاصيل الطلب</h3>
      <div id="order-detail-body"></div>
    </div>
  </main>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
  requireAdminAuth();
  renderSidebar('orders');
  const PAY_LABELS = { cod: 'الدفع عند الاستلام', jib: 'جيب JIB', kareemi: 'كريمي Kareemi' };
  const PAYMENT_STATUS_LABELS = { pending: 'قيد المراجعة', paid: 'مدفوع', failed: 'فشل', refunded: 'مسترجع' };

  async function loadOrders() {
    const res = await adminRequest('/orders?limit=100');
    const orders = (res && res.data) || [];
    document.getElementById('orders-tbody').innerHTML = orders.map(o => `
      <tr>
        <td>#${o.orderNumber}</td><td>${o.customerName}</td><td>${o.customerPhone}</td><td>${formatPrice(o.total)}</td>
        <td>${PAY_LABELS[o.paymentMethod] || o.paymentMethod}</td>
        <td><span class="status-pill status-${o.orderStatus}">${STATUS_LABELS[o.orderStatus]}</span></td>
        <td>${new Date(o.createdAt).toLocaleDateString('ar-YE')}</td>
        <td><button class="btn btn-outline btn-view" data-id="${o.id}">عرض</button></td>
      </tr>
    `).join('') || '<tr><td colspan="8">لا توجد طلبات بعد.</td></tr>';

    document.querySelectorAll('.btn-view').forEach(btn => btn.addEventListener('click', () => showOrder(btn.dataset.id)));
  }

  async function showOrder(id) {
    const res = await adminRequest(`/orders/${id}`);
    if (!res || !res.success) return;
    const o = res.data;
    const card = document.getElementById('order-detail-card');
    card.style.display = 'block';
    document.getElementById('order-detail-body').innerHTML = `
      <p><strong>رقم الطلب:</strong> #${o.order_number}</p>
      <p><strong>العميل:</strong> ${o.customer_name} - ${o.customer_phone}</p>
      <p><strong>العنوان:</strong> ${o.customer_address}</p>
      <p><strong>ملاحظات:</strong> ${o.notes || '—'}</p>
      <h4 style="margin:14px 0 8px;">المنتجات</h4>
      <ul>${o.items.map(i => `<li>${i.product_name} × ${i.quantity} = ${formatPrice(i.total)}</li>`).join('')}</ul>
      <p style="margin-top:10px;"><strong>الإجمالي:</strong> ${formatPrice(o.total)}</p>

      ${o.payments && o.payments.length ? `
        <h4 style="margin:18px 0 8px;">معلومات الدفع</h4>
        ${o.payments.map(p => `
          <div style="background:var(--cream); border-radius:8px; padding:14px; margin-bottom:10px;">
            <p><strong>طريقة الدفع:</strong> ${PAY_LABELS[p.method] || p.method}</p>
            <p><strong>رقم العملية المُدخل من العميل:</strong> ${p.transaction_number || '—'}</p>
            <p><strong>حالة الدفع الحالية:</strong> <span class="status-pill status-${p.status === 'paid' ? 'delivered' : p.status === 'failed' ? 'cancelled' : 'pending'}">${PAYMENT_STATUS_LABELS[p.status] || p.status}</span></p>
            <div style="margin-top:8px;">
              <strong>صورة إيصال الدفع:</strong><br>
              ${p.receipt_image
                ? `<a href="${p.receipt_image}" target="_blank"><img src="${p.receipt_image}" alt="إيصال الدفع" style="max-width:260px; border-radius:8px; margin-top:6px; border:1px solid #e3d7c2;"></a>`
                : `<span style="color:#b91c1c;">لم يرفع العميل صورة إيصال لهذا الطلب.</span>`}
            </div>
            <div class="form-row" style="max-width:220px; margin-top:12px;">
              <label>تحديث حالة الدفع</label>
              <select class="payment-status-select" data-payment-id="${p.id}">
                ${Object.entries(PAYMENT_STATUS_LABELS).map(([k, v]) => `<option value="${k}" ${p.status === k ? 'selected' : ''}>${v}</option>`).join('')}
              </select>
            </div>
            <button class="btn btn-primary btn-sm save-payment-status" data-payment-id="${p.id}" style="margin-top:8px;">حفظ حالة الدفع</button>
          </div>
        `).join('')}
      ` : ''}

      <div class="form-row" style="margin-top:16px; max-width:260px;">
        <label>تغيير حالة الطلب</label>
        <select id="status-select">${Object.entries(STATUS_LABELS).map(([k, v]) => `<option value="${k}" ${o.order_status === k ? 'selected' : ''}>${v}</option>`).join('')}</select>
      </div>
      <button class="btn btn-primary" id="save-status-btn">حفظ الحالة</button>
    `;
    document.getElementById('save-status-btn').addEventListener('click', async () => {
      const status = document.getElementById('status-select').value;
      await adminRequest(`/orders/${id}/status`, { method: 'PUT', body: JSON.stringify({ status }) });
      loadOrders();
      showOrder(id);
    });

    document.querySelectorAll('.save-payment-status').forEach(btn => btn.addEventListener('click', async () => {
      const paymentId = btn.dataset.paymentId;
      const status = document.querySelector(`.payment-status-select[data-payment-id="${paymentId}"]`).value;
      await adminRequest(`/payments/${paymentId}/status`, { method: 'PUT', body: JSON.stringify({ status }) });
      showOrder(id);
    }));
  }

  loadOrders();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/admin/orders.blade.php ENDPATH**/ ?>