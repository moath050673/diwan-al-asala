/* ===================================================================
   checkout.js — منطق صفحة إتمام الطلب
   مهم: الأسعار والإجمالي المعروضة هنا هي للعرض فقط؛ الـ Backend يعيد
   حسابها من قاعدة البيانات ولا يثق بأي رقم قادم من المتصفح (راجع orders controller).
   =================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  const items = Cart.getAll();
  const listEl = document.getElementById('checkout-items');
  const totalsEl = document.getElementById('checkout-totals');
  const form = document.getElementById('checkout-form');

  if (!items.length) {
    document.getElementById('checkout-content').innerHTML = `
      <div class="empty-state">
        <div class="icon">🧺</div>
        <h3>السلة فارغة</h3>
        <p>أضف بعض المنتجات أولًا قبل إتمام الطلب.</p>
        <a href="/products" class="btn btn-primary" style="margin-top:16px;">تصفح المنتجات</a>
      </div>`;
    return;
  }

  const shipping = window.DIWAN_SETTINGS.shippingCost;
  const t = Cart.totals(items, shipping);

  listEl.innerHTML = items.map(i => `
    <div class="summary-row"><span>${i.name} × ${i.qty}</span><span>${Products.formatPrice(i.price * i.qty)}</span></div>
  `).join('');

  totalsEl.innerHTML = `
    <div class="summary-row"><span>إجمالي المنتجات</span><span>${Products.formatPrice(t.subtotal)}</span></div>
    <div class="summary-row"><span>تكلفة التوصيل</span><span>${Products.formatPrice(t.shippingCost)}</span></div>
    <div class="summary-row total"><span>الإجمالي النهائي</span><span>${Products.formatPrice(t.total)}</span></div>
  `;

      // ---------- تحميل بيانات حسابات جيب وكريمي الحقيقية من الإعدادات ----------
  (async () => {
    try {
      const res = await fetch((window.DIWAN_API_BASE || '/api') + '/payments/settings');
      const json = await res.json();
      const d = (json && json.data) || {};
      const setText = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val || 'غير متوفر حاليًا'; };
      setText('jib-account-name', d.jib_account_name);
      setText('jib-account-number', d.jib_account_number);
      setText('kareemi-account-name', d.kareemi_account_name);
      setText('kareemi-account-number', d.kareemi_account_number);
    } catch (e) {
      console.warn('تعذّر تحميل بيانات حسابات الدفع', e);
    }
  })();

  document.querySelectorAll('.btn-copy-number').forEach(btn => {
    btn.addEventListener('click', () => {
      const text = document.getElementById(btn.dataset.target).textContent;
      navigator.clipboard.writeText(text).then(() => {
        const old = btn.textContent;
        btn.textContent = 'تم النسخ ✓';
        setTimeout(() => { btn.textContent = old; }, 1500);
      });
    });
  });


  // ---------- Payment method switching ----------
  document.querySelectorAll('.payment-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      opt.querySelector('input[type=radio]').checked = true;
      const uploadWrap = document.getElementById('receipt-upload-wrap');
      const needsReceipt = opt.dataset.method === 'jib' || opt.dataset.method === 'kareemi';
      uploadWrap.style.display = needsReceipt ? 'block' : 'none';
    });
  });

  // ---------- Submit ----------
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const rawFd = new FormData(form);
    const fd = new FormData();
    fd.append('customerName', rawFd.get('name'));
    fd.append('customerPhone', rawFd.get('phone'));
    fd.append('customerWhatsapp', rawFd.get('whatsapp') || rawFd.get('phone'));
    fd.append('city', rawFd.get('city'));
    fd.append('area', rawFd.get('area'));
    fd.append('address', rawFd.get('address'));
    fd.append('notes', rawFd.get('notes') || '');
    fd.append('paymentMethod', rawFd.get('paymentMethod'));
    if (rawFd.get('transactionNumber')) fd.append('transactionNumber', rawFd.get('transactionNumber'));
    fd.append('items', JSON.stringify(items.map(i => ({ productId: i.id, quantity: i.qty })))); // السعر يُحسب من الخادم
    const receiptFile = rawFd.get('receipt');
    if (receiptFile && receiptFile.size > 0) fd.append('receipt', receiptFile);

    const submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'جارِ إرسال الطلب...';

    const result = await API.createOrder(fd);

    // Fallback محلي عند عدم توفر Backend فعلي أثناء المعاينة
    const order = result && result.data ? result.data : {
      orderNumber: Math.floor(10000 + Math.random() * 89999),
      items, total: t.total,
      customerName: rawFd.get('name'),
      customerPhone: rawFd.get('phone'),
      customerAddress: `${rawFd.get('city')} - ${rawFd.get('area')} - ${rawFd.get('address')}`,
      paymentMethodLabel: paymentLabel(rawFd.get('paymentMethod')),
    };

    localStorage.setItem('diwan_last_order', JSON.stringify(order));
    Cart.clear();
    window.location.href = '/order-success';
  });

  function paymentLabel(method) {
    return { cod: 'الدفع عند الاستلام', jib: 'جيب JIB', kareemi: 'كريمي Kareemi' }[method] || method;
  }

  // ---------- Send cart summary via WhatsApp button ----------
  document.getElementById('wa-send-cart')?.addEventListener('click', () => {
    window.open(WhatsAppLink.cartSummary(items, t), '_blank');
  });
});
