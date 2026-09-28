@extends('layouts.admin')
@section('title', 'المنتجات | لوحة تحكم متجر ديوان الأصالة')
@section('content')
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>المنتجات</h1><button class="btn btn-primary" id="add-product-btn" type="button">+ إضافة منتج</button></div>

    <div class="card" id="product-form-card" hidden>
      <h3 id="form-title" style="margin-bottom:14px; color:var(--primary);">إضافة منتج جديد</h3>
      <div class="notice err" id="form-error" hidden></div>
      <form id="product-form" novalidate>
        <input type="hidden" name="id">
        <div class="form-grid">
          <div class="form-row full">
            <label for="f-name">اسم المنتج <span class="req">*</span></label>
            <input type="text" id="f-name" name="name" required maxlength="200" placeholder="مثال: ميدالية ريزن بحرف A">
          </div>
          <div class="form-row">
            <label for="f-price">السعر (ريال) <span class="req">*</span></label>
            <input type="number" id="f-price" name="price" required min="0" step="1" inputmode="numeric" placeholder="1500">
          </div>
          <div class="form-row">
            <label for="f-old-price">السعر قبل الخصم</label>
            <input type="number" id="f-old-price" name="oldPrice" min="0" step="1" inputmode="numeric">
            <span class="hint">اختياري — يظهر مشطوبًا مع نسبة الخصم</span>
          </div>
          <div class="form-row">
            <label for="category-select">التصنيف <span class="req">*</span></label>
            <select id="category-select" name="categoryId" required></select>
          </div>
          <div class="form-row">
            <label for="f-stock">الكمية في المخزون <span class="req">*</span></label>
            <input type="number" id="f-stock" name="stockQuantity" required min="0" step="1" inputmode="numeric" placeholder="10">
            <span class="hint">تنقص تلقائيًا مع كل طلب، وتعود عند إلغاء الطلب</span>
          </div>
          <div class="form-row full">
            <label for="f-description">الوصف</label>
            <textarea id="f-description" name="description" rows="4" placeholder="المكونات، الحجم، طريقة الاستخدام..."></textarea>
          </div>

          <div class="form-row full">
            <label>صور المنتج</label>
            <label class="image-drop" id="image-drop" for="f-images">
              <strong>اضغط لاختيار الصور أو اسحبها هنا</strong>
              <span>حتى 10 صور · JPG أو PNG أو WEBP · 5 ميجابايت للصورة</span>
              <input type="file" id="f-images" accept="image/jpeg,image/png,image/webp" multiple>
            </label>
            <div class="image-grid" id="image-grid"></div>
            <span class="hint">الصورة المعلّمة بـ «رئيسية» تظهر في بطاقة المنتج، وباقي الصور يقلّبها العميل في صفحة المنتج.</span>
          </div>

          <div class="form-row">
            <label for="f-sku">رمز المنتج (SKU)</label>
            <input type="text" id="f-sku" name="sku" maxlength="60" placeholder="يُولَّد تلقائيًا">
          </div>
          <div class="form-row">
            <label for="f-status">الحالة</label>
            <select id="f-status" name="status">
              <option value="active">ظاهر في المتجر</option>
              <option value="inactive">مخفي</option>
            </select>
          </div>
          <div class="form-row full">
            <label class="check-row"><input type="checkbox" id="f-featured" name="featured"> منتج مميز (يظهر في الصفحة الرئيسية)</label>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary" id="save-btn">حفظ المنتج</button>
          <button type="button" class="btn btn-outline" id="cancel-form-btn">إلغاء</button>
        </div>
      </form>
    </div>

    <div class="card">
      <table class="admin-table responsive">
        <thead><tr><th>الصورة</th><th>الاسم</th><th>السعر</th><th>المخزون</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody id="products-tbody"><tr><td colspan="6">جارِ التحميل...</td></tr></tbody>
      </table>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
  requireAdminAuth();
  renderSidebar('products');

  const MAX_IMAGES = 10;
  const form = document.getElementById('product-form');
  const formCard = document.getElementById('product-form-card');
  const formError = document.getElementById('form-error');
  const imageGrid = document.getElementById('image-grid');
  const fileInput = document.getElementById('f-images');
  const saveBtn = document.getElementById('save-btn');

  let CATEGORIES = [];
  let existingImages = []; // [{id, url}] — الصور المحفوظة للمنتج المفتوح
  let pendingFiles = [];   // ملفات مختارة لم تُرفع بعد

  async function loadCategories() {
    const res = await adminRequest('/categories');
    CATEGORIES = (res && res.data) || [];
    document.getElementById('category-select').innerHTML =
      '<option value="">— اختر التصنيف —</option>' +
      CATEGORIES.map(c => `<option value="${escapeHtml(c.id)}">${escapeHtml(c.name)}</option>`).join('');
  }

  function stockCell(stock) {
    if (stock <= 0) return '<span class="stock-out">نفد</span>';
    if (stock <= 3) return `<span class="stock-low">${escapeHtml(stock)} (منخفض)</span>`;
    return escapeHtml(stock);
  }

  async function loadProducts() {
    const res = await adminRequest('/admin/products?limit=100');
    const products = (res && res.data) || [];
    document.getElementById('products-tbody').innerHTML = products.map(p => `
      <tr>
        <td data-label="">${p.image ? `<img class="thumb" src="${escapeHtml(p.image)}" alt="">` : '<span class="thumb thumb-empty">بدون صورة</span>'}</td>
        <td data-label="الاسم"><strong>${escapeHtml(p.name)}</strong><div class="muted">${escapeHtml(p.categoryName)} · ${escapeHtml(p.images.length)} صورة</div></td>
        <td data-label="السعر">${formatPrice(p.price)}</td>
        <td data-label="المخزون">${stockCell(p.stock)}</td>
        <td data-label="الحالة"><span class="status-pill ${p.status === 'active' ? 'status-delivered' : 'status-cancelled'}">${p.status === 'active' ? 'ظاهر' : 'مخفي'}</span></td>
        <td class="cell-actions">
          <button class="btn btn-outline btn-sm btn-edit" data-id="${escapeHtml(p.id)}" type="button">تعديل</button>
          ${p.status === 'active'
            ? `<button class="btn btn-danger btn-sm btn-hide" data-id="${escapeHtml(p.id)}" type="button">إخفاء</button>`
            : `<button class="btn btn-primary btn-sm btn-show" data-id="${escapeHtml(p.id)}" type="button">إظهار</button>`}
        </td>
      </tr>
    `).join('') || '<tr><td colspan="6">لا توجد منتجات بعد.</td></tr>';
  }

  document.getElementById('products-tbody').addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-id]');
    if (!btn) return;
    const id = btn.dataset.id;
    if (btn.classList.contains('btn-edit')) return openEdit(id);
    if (btn.classList.contains('btn-hide')) {
      if (!confirm('إخفاء هذا المنتج من المتجر؟ يمكنك إظهاره لاحقًا.')) return;
      await adminRequest(`/products/${id}`, { method: 'DELETE' });
    }
    if (btn.classList.contains('btn-show')) {
      await adminRequest(`/products/${id}`, { method: 'PUT', body: JSON.stringify({ status: 'active' }) });
    }
    loadProducts();
  });

  /* ---------- الصور ---------- */
  function renderImages() {
    const saved = existingImages.map((img, i) => `
      <div class="image-tile ${i === 0 ? 'is-primary' : ''}">
        <img src="${escapeHtml(img.url)}" alt="">
        ${i === 0 ? '<span class="badge">رئيسية</span>' : ''}
        <div class="tile-actions">
          ${i === 0 ? '' : `<button type="button" data-primary="${escapeHtml(img.id)}">رئيسية</button>`}
          <button type="button" class="danger" data-delete="${escapeHtml(img.id)}">حذف</button>
        </div>
      </div>`);
    const pending = pendingFiles.map((f, i) => `
      <div class="image-tile ${!existingImages.length && i === 0 ? 'is-primary' : ''}">
        <img src="${f.preview}" alt="">
        <span class="badge new">${!existingImages.length && i === 0 ? 'رئيسية · جديدة' : 'جديدة'}</span>
        <div class="tile-actions"><button type="button" class="danger" data-remove="${i}">إزالة</button></div>
      </div>`);
    imageGrid.innerHTML = saved.concat(pending).join('');
  }

  function addFiles(files) {
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    for (const file of files) {
      if (existingImages.length + pendingFiles.length >= MAX_IMAGES) { showError(`الحد الأقصى ${MAX_IMAGES} صور للمنتج`); break; }
      if (!allowed.includes(file.type)) { showError(`"${file.name}" ليست صورة JPG أو PNG أو WEBP`); continue; }
      if (file.size > 5 * 1024 * 1024) { showError(`"${file.name}" أكبر من 5 ميجابايت`); continue; }
      pendingFiles.push(Object.assign(file, { preview: URL.createObjectURL(file) }));
    }
    renderImages();
  }

  fileInput.addEventListener('change', () => { addFiles(fileInput.files); fileInput.value = ''; });
  const drop = document.getElementById('image-drop');
  ['dragenter', 'dragover'].forEach(ev => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('dragover'); }));
  ['dragleave', 'drop'].forEach(ev => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.remove('dragover'); }));
  drop.addEventListener('drop', (e) => addFiles(e.dataTransfer.files));

  imageGrid.addEventListener('click', async (e) => {
    const b = e.target.closest('button');
    if (!b) return;
    const productId = form.elements.id.value;
    if (b.dataset.remove !== undefined) {
      pendingFiles.splice(Number(b.dataset.remove), 1);
      return renderImages();
    }
    if (b.dataset.delete) {
      if (!confirm('حذف هذه الصورة نهائيًا؟')) return;
      await adminRequest(`/products/${productId}/images/${b.dataset.delete}`, { method: 'DELETE' });
      existingImages = existingImages.filter(img => String(img.id) !== b.dataset.delete);
      renderImages(); loadProducts();
    }
    if (b.dataset.primary) {
      await adminRequest(`/products/${productId}/images/${b.dataset.primary}/primary`, { method: 'PUT' });
      const img = existingImages.find(i => String(i.id) === b.dataset.primary);
      existingImages = [img, ...existingImages.filter(i => i !== img)];
      renderImages(); loadProducts();
    }
  });

  /* ---------- النموذج ---------- */
  function showError(msg) { formError.textContent = msg; formError.hidden = !msg; }

  function openForm(title) {
    showError('');
    document.getElementById('form-title').textContent = title;
    formCard.hidden = false;
    formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function resetForm() {
    form.reset();
    form.elements.id.value = '';
    existingImages = [];
    pendingFiles = [];
    renderImages();
  }

  document.getElementById('add-product-btn').addEventListener('click', () => {
    resetForm();
    openForm('إضافة منتج جديد');
    document.getElementById('f-name').focus();
  });
  document.getElementById('cancel-form-btn').addEventListener('click', () => { formCard.hidden = true; resetForm(); });

  async function openEdit(id) {
    const res = await adminRequest(`/admin/products/${id}`);
    const p = res && res.data;
    if (!p) { alert('تعذّر تحميل بيانات المنتج'); return; }
    resetForm();
    form.elements.id.value = p.id;
    form.elements.name.value = p.name || '';
    form.elements.price.value = p.price ?? '';
    form.elements.oldPrice.value = p.oldPrice ?? '';
    form.elements.categoryId.value = p.categoryId;
    form.elements.stockQuantity.value = p.stock ?? 0;
    form.elements.description.value = p.description || '';
    form.elements.sku.value = p.sku || '';
    form.elements.status.value = p.status;
    form.elements.featured.checked = !!p.featured;
    existingImages = p.imageItems || [];
    renderImages();
    openForm('تعديل: ' + p.name);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    showError('');
    const f = form.elements;
    if (!f.name.value.trim()) return showError('اكتب اسم المنتج');
    if (f.price.value === '' || Number(f.price.value) < 0) return showError('اكتب سعر المنتج');
    if (!f.categoryId.value) return showError('اختر التصنيف');
    if (f.stockQuantity.value === '') return showError('اكتب الكمية المتوفرة في المخزون');

    const id = f.id.value;
    const payload = {
      name: f.name.value.trim(),
      price: Number(f.price.value),
      oldPrice: f.oldPrice.value ? Number(f.oldPrice.value) : null,
      categoryId: Number(f.categoryId.value),
      stockQuantity: Number(f.stockQuantity.value),
      description: f.description.value.trim() || null,
      sku: f.sku.value.trim() || null,
      status: f.status.value,
      featured: f.featured.checked,
    };

    saveBtn.disabled = true;
    saveBtn.textContent = 'جارِ الحفظ...';
    try {
      const res = id
        ? await adminRequest(`/products/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
        : await adminRequest('/products', { method: 'POST', body: JSON.stringify(payload) });
      if (!res || res.success === false || res.errors) return showError(apiError(res, 'تعذّر حفظ المنتج، تحقّق من البيانات.'));

      const productId = id || res.data.id;
      if (pendingFiles.length) {
        saveBtn.textContent = `جارِ رفع ${pendingFiles.length} صورة...`;
        const fd = new FormData();
        pendingFiles.forEach(file => fd.append('images[]', file));
        const up = await adminUpload(`/products/${productId}/images`, fd);
        if (!up || up.success === false || up.errors) {
          // المنتج حُفظ لكن الصور فشلت — نبقي النموذج مفتوحًا لإعادة المحاولة
          form.elements.id.value = productId;
          await loadProducts();
          return showError('تم حفظ المنتج، لكن تعذّر رفع الصور: ' + apiError(up, 'حاول مرة أخرى'));
        }
      }

      formCard.hidden = true;
      resetForm();
      await loadProducts();
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = 'حفظ المنتج';
    }
  });

  (async () => { await loadCategories(); await loadProducts(); })();
</script>
@endpush
