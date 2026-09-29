/* ===================================================================
   checkout.js — منطق صفحة إتمام الطلب
   مهم: الأسعار والإجمالي المعروضة هنا هي للعرض فقط؛ الـ Backend يعيد
   حسابها من قاعدة البيانات ولا يثق بأي رقم قادم من المتصفح (راجع orders controller).
   =================================================================== */

document.addEventListener('DOMContentLoaded', async () => {
  // الأسعار المحفوظة في المتصفح قد تكون قديمة — نعرض للعميل الأسعار الحالية قبل التأكيد
  const sync = await Cart.syncWithStore();
  Cart.syncNotice(sync);
  const items = sync.items;
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
  const deliveryBox = document.getElementById('co-delivery');
  const deliveryFields = document.querySelectorAll('[data-delivery-field]');
  let t = Cart.totals(items, 0);

  listEl.innerHTML = items.map(i => `
    <div class="summary-row"><span>${escapeHtml(i.name)} × ${escapeHtml(i.qty)}</span><span>${Products.formatPrice(i.price * i.qty)}</span></div>
  `).join('');

  // ---------- خدمة التوصيل (اختيارية) ----------
  // مفعّلة: تظهر حقول العنوان وتُضاف تكلفة التوصيل. غير مفعّلة: استلام من المتجر بدون تكلفة.
  if (shipping > 0) document.getElementById('delivery-cost-label').textContent = `(+${Products.formatPrice(shipping)})`;

  function applyDelivery() {
    const on = deliveryBox.checked;
    deliveryBox.closest('.delivery-option').classList.toggle('selected', on);
    deliveryFields.forEach(group => {
      group.style.display = on ? '' : 'none';
      // حقول العنوان مطلوبة فقط مع التوصيل (حقل مخفي ومطلوب كان سيمنع إرسال النموذج)
      group.querySelectorAll('input, textarea').forEach(el => { el.required = on; });
    });

    t = Cart.totals(items, on ? shipping : 0);
    totalsEl.innerHTML = `
      <div class="summary-row"><span>إجمالي المنتجات</span><span>${Products.formatPrice(t.subtotal)}</span></div>
      <div class="summary-row"><span>التوصيل</span><span>${on ? Products.formatPrice(t.shippingCost) : 'استلام من المتجر'}</span></div>
      <div class="summary-row total"><span>الإجمالي النهائي</span><span>${Products.formatPrice(t.total)}</span></div>
    `;
  }
  deliveryBox.addEventListener('change', applyDelivery);
  applyDelivery();

  // بيانات حسابات جيب وكريمي مكتوبة في الصفحة من الخادم (PageController::checkout)

  /**
   * نسخ نص إلى الحافظة. navigator.clipboard يعمل فقط على HTTPS أو localhost،
   * لذلك على الجوال عبر http://192.168.x.x نستخدم الطريقة القديمة (execCommand).
   */
  async function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      try { await navigator.clipboard.writeText(text); return true; } catch (e) { /* نجرب الطريقة البديلة */ }
    }
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.setAttribute('readonly', '');
    ta.style.cssText = 'position:fixed; top:0; left:0; opacity:0; font-size:16px;'; // 16px يمنع تكبير iOS
    document.body.appendChild(ta);
    ta.select();
    ta.setSelectionRange(0, text.length);
    let ok = false;
    try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
    ta.remove();
    return ok;
  }

  document.querySelectorAll('.btn-copy-number').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();   // الزر داخل <label> — نمنع تغيير طريقة الدفع عند الضغط عليه
      e.stopPropagation();
      const target = document.getElementById(btn.dataset.target);
      const text = target.textContent.trim().replace(/\s+/g, '');
      const old = btn.dataset.label || (btn.dataset.label = btn.textContent);
      if (await copyText(text)) {
        btn.textContent = 'تم النسخ ✓';
        btn.classList.add('copied');
      } else {
        // آخر حل: تحديد الرقم ليضغط العميل مطولًا ويختار "نسخ"
        const range = document.createRange();
        range.selectNodeContents(target);
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        btn.textContent = 'اضغط مطولًا على الرقم للنسخ';
      }
      setTimeout(() => { btn.textContent = old; btn.classList.remove('copied'); }, 2200);
    });
  });

  // إذا كان "الدفع عند الاستلام" معطّلًا من لوحة التحكم، نختار أول طريقة متاحة
  if (!document.querySelector('.payment-methods .payment-option input:checked')) {
    const first = document.querySelector('.payment-methods .payment-option');
    if (first) setTimeout(() => first.click()); // بعد ربط معالج التبديل أدناه (يُظهر حقل الإيصال إن لزم)
  }


  // ---------- Payment method switching ----------
  // .payment-methods فقط — خيار التوصيل له نفس الشكل لكنه ليس طريقة دفع
  document.querySelectorAll('.payment-methods .payment-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.payment-methods .payment-option').forEach(o => o.classList.remove('selected'));
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
    const withDelivery = deliveryBox.checked;
    fd.append('delivery', withDelivery ? '1' : '0');
    if (withDelivery) {
      fd.append('city', rawFd.get('city'));
      fd.append('area', rawFd.get('area') || '');
      fd.append('address', rawFd.get('address'));
    }
    fd.append('notes', rawFd.get('notes') || '');
    fd.append('paymentMethod', rawFd.get('paymentMethod'));
    if (rawFd.get('transactionNumber')) fd.append('transactionNumber', rawFd.get('transactionNumber'));
    fd.append('items', JSON.stringify(items.map(i => ({ productId: i.id, quantity: i.qty })))); // السعر يُحسب من الخادم
    const receiptFile = rawFd.get('receipt');
    if (receiptFile && receiptFile.size > 0) {
      // نفس حدود الخادم — رسالة فورية بدل انتظار رفع ملف سيُرفض
      if (!['image/jpeg', 'image/png', 'image/webp'].includes(receiptFile.type)) {
        showToast('صورة الإيصال يجب أن تكون JPG أو PNG أو WEBP', 'error');
        return;
      }
      if (receiptFile.size > 4 * 1024 * 1024) {
        showToast('حجم صورة الإيصال يجب ألا يتجاوز 4 ميجابايت', 'error');
        return;
      }
      fd.append('receipt', receiptFile);
    }

    const submitBtn = form.querySelector('button[type=submit]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'جارِ إرسال الطلب...';

    const result = await API.createOrder(fd);

    // لا نعرض صفحة نجاح إلا إذا حفظ الخادم الطلب فعلًا — في حالة الخطأ (نفاد المخزون،
    // بيانات ناقصة، انقطاع الاتصال) تبقى السلة كما هي ويرى العميل سبب المشكلة.
    if (!result.ok || !result.data || !result.data.data) {
      showToast(API.errorMessage(result, 'تعذّر إرسال الطلب، يرجى المحاولة مجددًا أو التواصل معنا عبر واتساب.'), 'error');
      submitBtn.disabled = false;
      submitBtn.textContent = 'تأكيد الطلب';
      return;
    }

    localStorage.setItem('diwan_last_order', JSON.stringify(result.data.data));
    Cart.clear();
    window.location.href = '/order-success';
  });

  // ---------- Send cart summary via WhatsApp button ----------
  document.getElementById('wa-send-cart')?.addEventListener('click', () => {
    window.open(WhatsAppLink.cartSummary(items, t), '_blank');
  });
});
