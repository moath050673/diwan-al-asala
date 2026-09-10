<?php $__env->startSection('title', 'تسجيل الدخول | لوحة تحكم متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="login-wrap">
  <div class="login-card">
    <h2>متجر ديوان الأصالة</h2>
    <p>لوحة تحكم المتجر</p>
    <div class="error-msg" id="login-error"></div>
    <form id="login-form">
      <div class="form-row"><label>البريد الإلكتروني</label><input type="email" name="email" required></div>
      <div class="form-row"><label>كلمة المرور</label><input type="password" name="password" required></div>
      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">تسجيل الدخول</button>
    </form>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
  if (AdminAuth.isLoggedIn()) window.location.href = '/admin/dashboard';

  document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const errEl = document.getElementById('login-error');
    errEl.style.display = 'none';
    try {
      const res = await fetch(ADMIN_API_BASE + '/auth/login', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: fd.get('email'), password: fd.get('password') }),
      });
      const json = await res.json();
      if (!res.ok || !json.success) throw new Error(json.message || 'فشل تسجيل الدخول');
      AdminAuth.setToken(json.data.token);
      if (json.data.mustChangePassword) alert('يجب تغيير كلمة المرور عند أول تسجيل دخول.');
      window.location.href = '/admin/dashboard';
    } catch (err) {
      errEl.textContent = err.message;
      errEl.style.display = 'block';
    }
  });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/admin/login.blade.php ENDPATH**/ ?>