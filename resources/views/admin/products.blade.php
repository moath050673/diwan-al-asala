@extends('layouts.admin')
@section('title', 'المنتجات | لوحة تحكم متجر ديوان الأصالة')
@section('content')
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>المنتجات</h1><button class="btn btn-primary" id="add-product-btn">+ إضافة منتج</button></div>

    <div class="card" id="product-form-card" style="display:none;">
      <h3 id="form-title" style="margin-bottom:14px; color:var(--primary);">إضافة منتج جديد</h3>
      <form id="product-form">
        <input type="hidden" name="id">
        <div class="form-row"><label>اسم المنتج *</label><input type="text" name="name" required></div>
        <div class="form-row"><label>SKU *</label><input type="text" name="sku" required></div>
        <div class="form-row"><label>التصنيف *</label><select name="categoryId" id="category-select" required></select></div>
        <div class="form-row"><label>الوصف</label><textarea name="description" rows="3"></textarea></div>
        <div class="form-row"><label>السعر *</label><input type="number" name="price" required></div>
        <div class="form-row"><label>السعر القديم</label><input type="number" name="oldPrice"></div>
        <div class="form-row"><label>الكمية في المخزون *</label><input type="number" name="stockQuantity" required></div>
        <div class="form-row"><label><input type="checkbox" name="featured" style="width:auto; display:inline-block;"> منتج مميز</label></div>
        <button type="submit" class="btn btn-primary">حفظ</button>
        <button type="button" class="btn btn-outline" id="cancel-form-btn">إلغاء</button>
      </form>
    </div>

    <div class="card">
      <table class="admin-table">
        <thead><tr><th>الاسم</th><th>SKU</th><th>السعر</th><th>المخزون</th><th>الحالة</th><th>إجراءات</th></tr></thead>
        <tbody id="products-tbody"></tbody>
      </table>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
  requireAdminAuth();
  renderSidebar('products');
  let CATEGORIES = [];

  async function loadCategories() {
    const res = await adminRequest('/categories');
    CATEGORIES = (res && res.data) || [];
    document.getElementById('category-select').innerHTML = CATEGORIES.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  }

  async function loadProducts() {
    const res = await adminRequest('/products?limit=100');
    const products = (res && res.data) || [];
    document.getElementById('products-tbody').innerHTML = products.map(p => `
      <tr>
        <td>${p.name}</td><td>${p.sku}</td><td>${formatPrice(p.price)}</td><td>${p.stock}</td>
        <td><span class="status-pill ${p.stock > 0 ? 'status-delivered' : 'status-cancelled'}">${p.stock > 0 ? 'متوفر' : 'نفد'}</span></td>
        <td><button class="btn btn-outline btn-edit" data-id="${p.id}">تعديل</button> <button class="btn btn-danger btn-delete" data-id="${p.id}">حذف</button></td>
      </tr>
    `).join('') || '<tr><td colspan="6">لا توجد منتجات بعد.</td></tr>';

    document.querySelectorAll('.btn-delete').forEach(btn => btn.addEventListener('click', async () => {
      if (!confirm('هل تريد إخفاء هذا المنتج؟')) return;
      await adminRequest(`/products/${btn.dataset.id}`, { method: 'DELETE' });
      loadProducts();
    }));

    document.querySelectorAll('.btn-edit').forEach(btn => btn.addEventListener('click', async () => {
      const res = await adminRequest(`/products/${btn.dataset.id}`);
      const p = res && res.data;
      if (!p) { alert('تعذّر تحميل بيانات المنتج'); return; }

      const form = document.getElementById('product-form');
      form.reset();
      form.querySelector('[name="id"]').value = p.id;
      form.querySelector('[name="name"]').value = p.name || '';
      form.querySelector('[name="sku"]').value = p.sku || '';
      form.querySelector('[name="description"]').value = p.description || '';
      form.querySelector('[name="price"]').value = p.price ?? '';
      form.querySelector('[name="oldPrice"]').value = p.oldPrice ?? '';
      form.querySelector('[name="stockQuantity"]').value = p.stock ?? '';
      form.querySelector('[name="featured"]').checked = !!p.featured;

      // نطابق التصنيف عبر slug (category) لأن الـ API لا يُرجع categoryId مباشرة
      const matchedCat = CATEGORIES.find(c => c.slug === p.category);
      if (matchedCat) form.querySelector('[name="categoryId"]').value = matchedCat.id;

      document.getElementById('form-title').textContent = 'تعديل منتج';
      formCard.style.display = 'block';
      formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
  }

  const formCard = document.getElementById('product-form-card');
  document.getElementById('add-product-btn').addEventListener('click', () => {
    document.getElementById('product-form').reset();
    document.getElementById('product-form').querySelector('[name="id"]').value = '';
    document.getElementById('form-title').textContent = 'إضافة منتج جديد';
    formCard.style.display = 'block';
  });
  document.getElementById('cancel-form-btn').addEventListener('click', () => formCard.style.display = 'none');

  document.getElementById('product-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const id = fd.get('id');
    const payload = {
      name: fd.get('name'), sku: fd.get('sku'), categoryId: fd.get('categoryId'),
      description: fd.get('description'), price: Number(fd.get('price')),
      oldPrice: fd.get('oldPrice') ? Number(fd.get('oldPrice')) : null,
      stockQuantity: Number(fd.get('stockQuantity')), featured: fd.get('featured') === 'on',
    };

    const res = id
      ? await adminRequest(`/products/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
      : await adminRequest('/products', { method: 'POST', body: JSON.stringify(payload) });

    if (!res || res.success === false || res.errors) {
      const msg = (res && (res.message || Object.values(res.errors || {}).flat().join('\n'))) || 'تعذّر حفظ المنتج، تحقّق من البيانات المدخلة.';
      alert(msg);
      return;
    }

    formCard.style.display = 'none';
    loadProducts();
  });

  (async () => { await loadCategories(); await loadProducts(); })();
</script>
@endpush
