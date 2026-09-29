@extends('layouts.admin')
@section('title', 'الإعدادات | لوحة تحكم متجر ديوان الأصالة')
@section('content')
<div class="admin-layout">
  <aside class="sidebar" id="admin-sidebar"></aside>
  <main class="main">
    <div class="topbar"><h1>إعدادات المتجر</h1></div>
    <div class="card">
      <form id="settings-form">
        <div class="form-row"><label>اسم المتجر</label><input type="text" name="store_name"></div>
        <div class="form-row"><label>رقم واتساب المتجر</label><input type="text" name="whatsapp_number"></div>
        <div class="form-row"><label>رابط فيسبوك</label><input type="text" name="facebook_url"></div>
        <div class="form-row"><label>رابط انستقرام</label><input type="text" name="instagram_url"></div>
        <div class="form-row"><label>تكلفة التوصيل (صنعاء)</label><input type="number" name="shipping_cost_sanaa"></div>
        <hr style="margin:18px 0; border-color:var(--cream);">
        <div class="form-row"><label>اسم حساب جيب</label><input type="text" name="jib_account_name"></div>
        <div class="form-row"><label>رقم حساب جيب</label><input type="text" name="jib_account_number"></div>
        <div class="form-row"><label>اسم حساب كريمي</label><input type="text" name="kareemi_account_name"></div>
        <div class="form-row"><label>رقم حساب كريمي</label><input type="text" name="kareemi_account_number"></div>
        <button type="submit" class="btn btn-primary">حفظ الإعدادات</button>
      </form>
    </div>

    <div class="card" id="password" style="margin-top:20px;">
      <h3 style="margin-bottom:14px;">تغيير كلمة المرور</h3>
      <form id="password-form" autocomplete="off">
        <div class="form-row"><label>كلمة المرور الحالية</label><input type="password" name="currentPassword" required autocomplete="current-password"></div>
        <div class="form-row"><label>كلمة المرور الجديدة (10 أحرف على الأقل، حروف وأرقام)</label><input type="password" name="newPassword" required minlength="10" maxlength="128" autocomplete="new-password"></div>
        <div class="form-row"><label>تأكيد كلمة المرور الجديدة</label><input type="password" name="confirmPassword" required autocomplete="new-password"></div>
        <button type="submit" class="btn btn-primary">تغيير كلمة المرور</button>
      </form>
    </div>
  </main>
</div>
@endsection
@push('scripts')
<script>
  requireAdminAuth();
  renderSidebar('settings');
  const form = document.getElementById('settings-form');
  (async () => {
    const res = await adminRequest('/settings/all');
    if (res && res.data) Object.entries(res.data).forEach(([k, v]) => { const input = form.elements[k]; if (input) input.value = v; });
  })();
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const res = await adminRequest('/settings', { method: 'PUT', body: JSON.stringify(Object.fromEntries(fd.entries())) });
    if (!res || res.success === false || res.errors) {
      alert((res && (Object.values(res.errors || {}).flat().join('\n') || res.message)) || 'تعذّر حفظ الإعدادات');
      return;
    }
    alert('تم حفظ الإعدادات بنجاح');
  });

  const pwdForm = document.getElementById('password-form');
  pwdForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(pwdForm).entries());
    if (data.newPassword !== data.confirmPassword) {
      alert('تأكيد كلمة المرور غير مطابق');
      return;
    }
    const res = await adminRequest('/auth/change-password', {
      method: 'POST',
      body: JSON.stringify({ currentPassword: data.currentPassword, newPassword: data.newPassword }),
    });
    if (!res || res.success === false || res.errors) {
      alert(apiError(res, 'تعذّر تغيير كلمة المرور'));
      return;
    }
    pwdForm.reset();
    alert('تم تغيير كلمة المرور بنجاح. تم تسجيل الخروج من الأجهزة الأخرى.');
  });
</script>
@endpush
