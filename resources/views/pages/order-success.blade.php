@extends('layouts.app')
@section('title', 'تم استلام طلبك | متجر ديوان الأصالة')
@section('robots', 'noindex, follow')
@section('content')
<div class="section">
  <div class="container" style="max-width:600px;">
    <div class="order-success-card" id="order-success-card"></div>
  </div>
</div>
@endsection
@push('scripts')
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
        <div class="row"><span>رقم الطلب</span><strong>#${escapeHtml(order.orderNumber)}</strong></div>
        <div class="row"><span>اسم العميل</span><strong>${escapeHtml(order.customerName)}</strong></div>
        <div class="row"><span>الإجمالي</span><strong>${Products.formatPrice(order.total)}</strong></div>
        <div class="row"><span>طريقة الدفع</span><strong>${escapeHtml(order.paymentMethodLabel)}</strong></div>
        <div class="row"><span>حالة الطلب</span><strong>جديد</strong></div>
      </div>
      <a href="${WhatsAppLink.orderConfirmation(order)}" target="_blank" rel="noopener" class="btn btn-whatsapp">تواصل معنا عبر واتساب</a>
      <div style="margin-top:14px;"><a href="/products" class="btn btn-outline">متابعة التسوق</a></div>
    `;
  });
</script>
@endpush
