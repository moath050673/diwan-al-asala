@extends('layouts.app')
@section('title', 'سلة المشتريات | متجر ديوان الأصالة')
@section('content')
<div class="page-header">
  <div class="container"><h1>سلة المشتريات</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / السلة</div></div>
</div>
<div class="section">
  <div class="container split-layout split-cart" id="cart-content">
    <div>
      <table class="cart-table" id="cart-table">
        <thead><tr><th>المنتج</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th><th></th></tr></thead>
        <tbody id="cart-items-body"></tbody>
      </table>
    </div>
    <div class="cart-summary" id="cart-summary-box">
      <h3 style="margin-bottom:14px; color:var(--primary); font-family:var(--font-display);">ملخص الطلب</h3>
      <div id="cart-totals"></div>
      <a href="/checkout" class="btn btn-primary btn-block" style="margin-top:16px;">إتمام الطلب</a>
      <button class="btn btn-whatsapp btn-block" id="wa-send-cart-btn" style="margin-top:10px;">إرسال الطلب عبر واتساب</button>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
  function renderCart() {
    const items = Cart.getAll();
    const body = document.getElementById('cart-items-body');
    const wrap = document.getElementById('cart-content');

    if (!items.length) {
      wrap.style.gridTemplateColumns = '1fr';
      wrap.innerHTML = `<div class="empty-state"><div class="icon">🧺</div><h3>سلتك فارغة</h3><p>لم تقم بإضافة أي منتجات بعد.</p><a href="/products" class="btn btn-primary" style="margin-top:16px;">تصفح المنتجات</a></div>`;
      return;
    }

    body.innerHTML = items.map(i => `
      <tr data-id="${escapeHtml(i.id)}">
        <td><div class="cart-item-info"><div class="cart-item-thumb">${i.image ? `<img src="${escapeHtml(i.image)}" alt="" loading="lazy">` : "🧴"}</div><span>${escapeHtml(i.name)}</span></div></td>
        <td>${Products.formatPrice(i.price)}</td>
        <td><div class="qty-control"><button class="qty-dec">−</button><input type="number" class="qty-val" value="${escapeHtml(i.qty)}" min="1"><button class="qty-inc">+</button></div></td>
        <td>${Products.formatPrice(i.price * i.qty)}</td>
        <td><button class="remove-item">حذف</button></td>
      </tr>
    `).join('');

    const shipping = window.DIWAN_SETTINGS.shippingCost;
    const t = Cart.totals(items, shipping);
    document.getElementById('cart-totals').innerHTML = `
      <div class="summary-row"><span>إجمالي المنتجات</span><span>${Products.formatPrice(t.subtotal)}</span></div>
      <div class="summary-row"><span>تكلفة التوصيل</span><span>${Products.formatPrice(t.shippingCost)}</span></div>
      <div class="summary-row total"><span>الإجمالي النهائي</span><span>${Products.formatPrice(t.total)}</span></div>
    `;

    document.getElementById('wa-send-cart-btn').onclick = () => window.open(WhatsAppLink.cartSummary(items, t), '_blank');

    body.querySelectorAll('tr').forEach(row => {
      const id = row.dataset.id;
      row.querySelector('.qty-inc').addEventListener('click', () => { Cart.updateQty(id, +row.querySelector('.qty-val').value + 1); renderCart(); });
      row.querySelector('.qty-dec').addEventListener('click', () => { Cart.updateQty(id, Math.max(1, +row.querySelector('.qty-val').value - 1)); renderCart(); });
      row.querySelector('.qty-val').addEventListener('change', (e) => { Cart.updateQty(id, Math.max(1, +e.target.value)); renderCart(); });
      row.querySelector('.remove-item').addEventListener('click', () => { Cart.remove(id); renderCart(); showToast('تم حذف المنتج من السلة', 'success'); });
    });
  }

  document.addEventListener('DOMContentLoaded', () => { renderHeader(''); renderFooter(); renderCart(); });
</script>
@endpush
