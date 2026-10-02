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
    deliveryFields.forEach(group => { group.style.display = on ? '' : 'none'; });
    updateAddressRequired();

    t = Cart.totals(items, on ? shipping : 0);
    totalsEl.innerHTML = `
      <div class="summary-row"><span>إجمالي المنتجات</span><span>${Products.formatPrice(t.subtotal)}</span></div>
      <div class="summary-row"><span>التوصيل</span><span>${on ? Products.formatPrice(t.shippingCost) : 'استلام من المتجر'}</span></div>
      <div class="summary-row total"><span>الإجمالي النهائي</span><span>${Products.formatPrice(t.total)}</span></div>
    `;
  }
  // ---------- موقع التوصيل من الخريطة (اختياري) ----------
  // العميل يكتب عنوانه أو يحدد موقعه (أو كلاهما) — العنوان المكتوب مطلوب فقط إذا لم يُحدَّد موقع.
  const latInput = document.getElementById('co-lat');
  const lngInput = document.getElementById('co-lng');
  const addressInput = document.getElementById('co-address');
  const mapEl = document.getElementById('location-map');
  const mapStatus = document.getElementById('map-status');
  const mapClearBtn = document.getElementById('map-clear');
  const SANAA = [15.3694, 44.1910];
  let map = null;
  let marker = null;
  let leafletLoading = null;

  function hasLocation() { return latInput.value !== '' && lngInput.value !== ''; }

  // حقل مخفي ومطلوب كان سيمنع إرسال النموذج — لذلك المطلوب يتبع حالة التوصيل
  function updateAddressRequired() {
    const on = deliveryBox.checked;
    document.getElementById('co-city').required = on;
    addressInput.required = on && !hasLocation();
  }

  // Leaflet يُحمَّل عند الحاجة فقط — لا يثقل الصفحة على من يكتب عنوانه
  function loadLeaflet() {
    if (window.L) return Promise.resolve();
    if (leafletLoading) return leafletLoading;
    leafletLoading = new Promise((resolve, reject) => {
      const css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = window.LEAFLET_ASSETS.css;
      document.head.appendChild(css);
      const js = document.createElement('script');
      js.src = window.LEAFLET_ASSETS.js;
      js.onload = resolve;
      js.onerror = () => { leafletLoading = null; reject(); };
      document.head.appendChild(js);
    });
    return leafletLoading;
  }

  function setLocation(lat, lng) {
    latInput.value = lat.toFixed(7);
    lngInput.value = lng.toFixed(7);
    if (marker) marker.setLatLng([lat, lng]);
    mapStatus.textContent = '✓ تم تحديد موقعك على الخريطة — يمكنك سحب العلامة لتعديله.';
    mapStatus.classList.add('ok');
    mapClearBtn.hidden = false;
    updateAddressRequired();
  }

  async function openMap(center) {
    try {
      await loadLeaflet();
    } catch (e) {
      showToast('تعذّر تحميل الخريطة، يرجى كتابة العنوان بالتفصيل.', 'error');
      return false;
    }
    mapEl.hidden = false;
    const start = center || (hasLocation() ? [+latInput.value, +lngInput.value] : SANAA);
    if (!map) {
      map = L.map(mapEl).setView(start, center ? 16 : 13);
      L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
      }).addTo(map);
      marker = L.marker(start, { draggable: true }).addTo(map);
      marker.on('dragend', () => { const p = marker.getLatLng(); setLocation(p.lat, p.lng); });
      map.on('click', (e) => setLocation(e.latlng.lat, e.latlng.lng));
    } else {
      map.invalidateSize();
      map.setView(start, center ? 16 : map.getZoom());
      marker.setLatLng(start);
    }
    return true;
  }

  document.getElementById('map-open').addEventListener('click', () => openMap());

  document.getElementById('map-use-current').addEventListener('click', (e) => {
    if (!navigator.geolocation || !window.isSecureContext) {
      showToast('تحديد الموقع التلقائي غير متاح في هذا المتصفح، اختر موقعك من الخريطة.', 'error');
      openMap();
      return;
    }
    const btn = e.currentTarget;
    const old = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'جارِ تحديد موقعك...';
    navigator.geolocation.getCurrentPosition(async (pos) => {
      btn.disabled = false;
      btn.textContent = old;
      const { latitude, longitude } = pos.coords;
      if (await openMap([latitude, longitude])) setLocation(latitude, longitude);
    }, () => {
      btn.disabled = false;
      btn.textContent = old;
      showToast('لم نتمكن من معرفة موقعك — تأكد من السماح بالوصول للموقع، أو اختره من الخريطة.', 'error');
      openMap();
    }, { enableHighAccuracy: true, timeout: 15000 });
  });

  mapClearBtn.addEventListener('click', () => {
    latInput.value = '';
    lngInput.value = '';
    mapEl.hidden = true;
    mapClearBtn.hidden = true;
    mapStatus.textContent = 'اضغط على الخريطة أو اسحب العلامة إلى مكان التوصيل بالضبط.';
    mapStatus.classList.remove('ok');
    updateAddressRequired();
  });

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
      fd.append('address', rawFd.get('address') || '');
      if (hasLocation()) {
        fd.append('locationLat', latInput.value);
        fd.append('locationLng', lngInput.value);
      }
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
