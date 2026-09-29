@extends('layouts.admin')
@section('title', 'الطلبات | لوحة تحكم متجر ديوان الأصالة')
@section('content')
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>الطلبات</h1></div>
    <div class="notice ok" id="orders-notice" hidden></div>

    <div class="card danger-zone" id="delete-panel" hidden>
      <h3>حذف <span id="delete-count">0</span> طلب نهائيًا</h3>
      <p class="muted">يُستخدم لحذف الطلبات التجريبية أو الوهمية. لا يمكن التراجع عن الحذف.</p>
      <div class="options">
        <label><input type="checkbox" id="opt-restock" checked> إرجاع كميات المنتجات إلى المخزون</label>
        <label><input type="checkbox" id="opt-customers" checked> حذف العملاء الذين لن يبقى لهم أي طلب</label>
      </div>
      <div class="form-actions">
        <button class="btn btn-danger" id="confirm-delete-btn" type="button">نعم، احذف الطلبات المحددة</button>
        <button class="btn btn-outline" id="cancel-delete-btn" type="button">إلغاء</button>
      </div>
    </div>

    <div class="card">
      <div class="toolbar">
        <select id="status-filter" aria-label="تصفية حسب الحالة">
          <option value="">كل الحالات</option>
          <option value="pending">جديد</option><option value="confirmed">تم التأكيد</option>
          <option value="processing">قيد التجهيز</option><option value="shipped">تم الشحن</option>
          <option value="delivered">تم التسليم</option><option value="cancelled">ملغي</option>
        </select>
        <label class="check-row"><input type="checkbox" id="select-all"> تحديد الكل</label>
        <span class="spacer"></span>
        <button class="btn btn-danger btn-sm" id="delete-selected-btn" type="button" disabled>حذف المحدد</button>
      </div>
      <table class="admin-table responsive">
        <thead><tr><th></th><th>رقم الطلب</th><th>العميل</th><th>الهاتف</th><th>الإجمالي</th><th>الدفع</th><th>حالة الطلب</th><th>التاريخ</th><th></th></tr></thead>
        <tbody id="orders-tbody"><tr><td colspan="9">جارِ التحميل...</td></tr></tbody>
      </table>
    </div>
    <div class="card" id="order-detail-card" style="display:none;">
      <h3 style="margin-bottom:14px; color:var(--primary);">تفاصيل الطلب</h3>
      <div id="order-detail-body"></div>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
  requireAdminAuth();
  renderSidebar('orders');
  const PAY_LABELS = { cod: 'الدفع عند الاستلام', jib: 'جيب JIB', kareemi: 'كريمي Kareemi' };
  const PAYMENT_STATUS_LABELS = { pending: 'قيد المراجعة', paid: 'مدفوع', failed: 'فشل', refunded: 'مسترجع' };

  const tbody = document.getElementById('orders-tbody');
  const deleteBtn = document.getElementById('delete-selected-btn');
  const deletePanel = document.getElementById('delete-panel');
  const notice = document.getElementById('orders-notice');

  function showNotice(msg, ok = true) {
    notice.textContent = msg;
    notice.className = 'notice ' + (ok ? 'ok' : 'err');
    notice.hidden = !msg;
  }

  const selectedIds = () => [...tbody.querySelectorAll('.row-check:checked')].map(c => Number(c.value));

  function syncSelection() {
    const n = selectedIds().length;
    deleteBtn.disabled = !n;
    deleteBtn.textContent = n ? `حذف المحدد (${n})` : 'حذف المحدد';
    document.getElementById('delete-count').textContent = n;
    if (!n) deletePanel.hidden = true;
  }

  async function loadOrders() {
    const status = document.getElementById('status-filter').value;
    const res = await adminRequest('/orders?limit=100' + (status ? '&status=' + encodeURIComponent(status) : ''));
    const orders = (res && res.data) || [];
    tbody.innerHTML = orders.map(o => `
      <tr>
        <td data-label=""><input type="checkbox" class="row-check" value="${escapeHtml(o.id)}" aria-label="تحديد الطلب ${escapeHtml(o.orderNumber)}"></td>
        <td data-label="رقم الطلب">#${escapeHtml(o.orderNumber)}</td>
        <td data-label="العميل">${escapeHtml(o.customerName)}</td>
        <td data-label="الهاتف">${escapeHtml(o.customerPhone)}</td>
        <td data-label="الإجمالي">${formatPrice(o.total)}</td>
        <td data-label="الدفع">${escapeHtml(PAY_LABELS[o.paymentMethod] || o.paymentMethod)}</td>
        <td data-label="الحالة"><span class="status-pill status-${escapeHtml(o.orderStatus)}">${escapeHtml(STATUS_LABELS[o.orderStatus] || o.orderStatus)}</span></td>
        <td data-label="التاريخ">${new Date(o.createdAt).toLocaleDateString('ar-YE')}</td>
        <td class="cell-actions"><button class="btn btn-outline btn-sm btn-view" data-id="${escapeHtml(o.id)}" type="button">عرض</button></td>
      </tr>
    `).join('') || '<tr><td colspan="9">لا توجد طلبات.</td></tr>';

    document.getElementById('select-all').checked = false;
    syncSelection();
  }

  tbody.addEventListener('click', (e) => {
    const view = e.target.closest('.btn-view');
    if (view) showOrder(view.dataset.id);
  });
  tbody.addEventListener('change', (e) => { if (e.target.classList.contains('row-check')) syncSelection(); });
  document.getElementById('select-all').addEventListener('change', (e) => {
    tbody.querySelectorAll('.row-check').forEach(c => { c.checked = e.target.checked; });
    syncSelection();
  });
  document.getElementById('status-filter').addEventListener('change', loadOrders);

  deleteBtn.addEventListener('click', () => {
    deletePanel.hidden = false;
    deletePanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  document.getElementById('cancel-delete-btn').addEventListener('click', () => { deletePanel.hidden = true; });
  document.getElementById('confirm-delete-btn').addEventListener('click', async (e) => {
    const ids = selectedIds();
    if (!ids.length) return;
    e.target.disabled = true;
    const res = await adminRequest('/orders/delete', {
      method: 'POST',
      body: JSON.stringify({
        ids,
        restock: document.getElementById('opt-restock').checked,
        deleteCustomers: document.getElementById('opt-customers').checked,
      }),
    });
    e.target.disabled = false;
    deletePanel.hidden = true;
    document.getElementById('order-detail-card').style.display = 'none';
    showNotice(res && res.success ? res.message : apiError(res, 'تعذّر حذف الطلبات'), !!(res && res.success));
    loadOrders();
  });

  // طلب جديد وصل أثناء فتح الصفحة — نحدّث القائمة تلقائيًا
  document.addEventListener('admin:new-orders', () => loadOrders());

  async function showOrder(id) {
    const res = await adminRequest(`/orders/${id}`);
    if (!res || !res.success) return;
    const o = res.data;
    const card = document.getElementById('order-detail-card');
    card.style.display = 'block';
    requestAnimationFrame(() => card.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    document.getElementById('order-detail-body').innerHTML = `
      <p><strong>رقم الطلب:</strong> #${escapeHtml(o.order_number)}</p>
      <p><strong>العميل:</strong> ${escapeHtml(o.customer_name)} - ${escapeHtml(o.customer_phone)}</p>
      <p><strong>التوصيل:</strong> ${o.delivery
        ? `🚚 نعم — ${formatPrice(o.shipping_cost)}`
        : '<span style="color:#b45309; font-weight:700;">🏪 لا — استلام من المتجر</span>'}</p>
      ${o.delivery ? `<p><strong>العنوان:</strong> ${escapeHtml(o.customer_address)}</p>` : ''}
      <p><strong>ملاحظات:</strong> ${escapeHtml(o.notes || '—')}</p>
      <h4 style="margin:14px 0 8px;">المنتجات</h4>
      <ul>${o.items.map(i => `<li>${escapeHtml(i.product_name)} × ${escapeHtml(i.quantity)} = ${formatPrice(i.total)}</li>`).join('')}</ul>
      <p style="margin-top:10px;"><strong>الإجمالي:</strong> ${formatPrice(o.total)}</p>

      ${o.payments && o.payments.length ? `
        <h4 style="margin:18px 0 8px;">معلومات الدفع</h4>
        ${o.payments.map(p => `
          <div style="background:var(--cream); border-radius:8px; padding:14px; margin-bottom:10px;">
            <p><strong>طريقة الدفع:</strong> ${escapeHtml(PAY_LABELS[p.method] || p.method)}</p>
            <p><strong>رقم العملية المُدخل من العميل:</strong> ${escapeHtml(p.transaction_number || '—')}</p>
            <p><strong>حالة الدفع الحالية:</strong> <span class="status-pill status-${p.status === 'paid' ? 'delivered' : p.status === 'failed' ? 'cancelled' : 'pending'}">${escapeHtml(PAYMENT_STATUS_LABELS[p.status] || p.status)}</span></p>
            <div style="margin-top:8px;">
              <strong>صورة إيصال الدفع:</strong><br>
              ${p.receipt_url
                ? `<a class="receipt-link" data-receipt-url="${escapeHtml(p.receipt_url)}" target="_blank" rel="noopener"><img alt="جارِ تحميل الإيصال..." style="max-width:260px; border-radius:8px; margin-top:6px; border:1px solid #e3d7c2;"></a>`
                : `<span style="color:#b91c1c;">لم يرفع العميل صورة إيصال لهذا الطلب.</span>`}
            </div>
            <div class="form-row" style="max-width:220px; margin-top:12px;">
              <label>تحديث حالة الدفع</label>
              <select class="payment-status-select" data-payment-id="${escapeHtml(p.id)}">
                ${Object.entries(PAYMENT_STATUS_LABELS).map(([k, v]) => `<option value="${k}" ${p.status === k ? 'selected' : ''}>${v}</option>`).join('')}
              </select>
            </div>
            <button class="btn btn-primary btn-sm save-payment-status" data-payment-id="${escapeHtml(p.id)}" style="margin-top:8px;">حفظ حالة الدفع</button>
          </div>
        `).join('')}
      ` : ''}

      <div class="form-row" style="margin-top:16px; max-width:260px;">
        <label>تغيير حالة الطلب</label>
        <select id="status-select">${Object.entries(STATUS_LABELS).map(([k, v]) => `<option value="${k}" ${o.order_status === k ? 'selected' : ''}>${v}</option>`).join('')}</select>
      </div>
      <button class="btn btn-primary" id="save-status-btn">حفظ الحالة</button>
    `;
    // الإيصالات محمية بتسجيل الدخول، و<img src> لا يرسل ترويسة Authorization —
    // لذلك نجلب الصورة عبر fetch ونعرضها كـ blob محلي.
    document.querySelectorAll('.receipt-link').forEach(async (link) => {
      const res = await fetch(link.dataset.receiptUrl, { headers: { Authorization: `Bearer ${AdminAuth.getToken()}` } });
      const img = link.querySelector('img');
      if (!res.ok) { img.alt = 'تعذّر تحميل الإيصال'; return; }
      const blobUrl = URL.createObjectURL(await res.blob());
      img.src = blobUrl;
      img.alt = 'إيصال الدفع';
      link.href = blobUrl;
    });

    document.getElementById('save-status-btn').addEventListener('click', async () => {
      const status = document.getElementById('status-select').value;
      const res = await adminRequest(`/orders/${id}/status`, { method: 'PUT', body: JSON.stringify({ status }) });
      showNotice(res && res.success ? res.message : apiError(res, 'تعذّر تحديث حالة الطلب'), !!(res && res.success));
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

  // فتح طلب مباشرة من رابط الإشعار: /admin/orders?open=ID
  const openId = new URLSearchParams(location.search).get('open');
  if (openId && /^\d+$/.test(openId)) showOrder(openId);
</script>
@endpush
