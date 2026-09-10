<?php $__env->startSection('title', 'إتمام الطلب | متجر ديوان الأصالة'); ?>
<?php $__env->startSection('content'); ?>
<div class="page-header">
  <div class="container"><h1>إتمام الطلب</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / <a href="/cart">السلة</a> / إتمام الطلب</div></div>
</div>
<div class="section">
  <div class="container">
    <div class="checkout-steps">
      <div class="step"><span class="num">1</span> السلة</div>
      <div class="sep"></div>
      <div class="step active"><span class="num">2</span> بيانات الطلب</div>
      <div class="sep"></div>
      <div class="step"><span class="num">3</span> تأكيد الطلب</div>
    </div>

    <div id="checkout-content" style="display:grid; grid-template-columns:1.6fr 1fr; gap:30px; align-items:start;">
      <form id="checkout-form">
        <div class="form-grid">
          <div class="form-group full"><label>الاسم الكامل <span class="req">*</span></label><input type="text" name="name" required></div>
          <div class="form-group" id="phone-group">
            <label>رقم الهاتف <span class="req">*</span></label>
            <input type="tel" name="phone" id="phone-input" required pattern="[0-9]{9}" maxlength="9" inputmode="numeric" placeholder="7XXXXXXXX">
            <span class="form-error">رقم الهاتف يجب أن يتكون من 9 أرقام بالضبط</span>
          </div>
          <div class="form-group"><label>رقم واتساب</label><input type="tel" name="whatsapp"></div>
          <div class="form-group"><label>المحافظة</label><input type="text" name="city" value="صنعاء" required></div>
          <div class="form-group"><label>المدينة / المنطقة</label><input type="text" name="area" required></div>
          <div class="form-group full"><label>العنوان بالتفصيل <span class="req">*</span></label><textarea name="address" rows="2" required></textarea></div>
          <div class="form-group full"><label>ملاحظات الطلب</label><textarea name="notes" rows="2"></textarea></div>
        </div>

        <h3 style="margin:20px 0 12px; color:var(--primary); font-family:var(--font-display);">طريقة الدفع</h3>
        <div class="payment-methods">
          <label class="payment-option selected" data-method="cod">
            <input type="radio" name="paymentMethod" value="cod" checked>
            <div><div class="pm-title">الدفع عند الاستلام</div><div class="pm-desc">ادفع نقدًا عند استلام طلبك، لا حاجة لأي إيصال.</div></div>
          </label>
          <label class="payment-option" data-method="jib">
            <input type="radio" name="paymentMethod" value="jib">
            <div style="width:100%;">
              <div class="pm-title">جيب JIB</div>
                              <div class="row"><span>اسم الحساب</span><strong id="jib-account-name">—</strong></div>
                <div class="row"><span>رقم الحساب</span><strong id="jib-account-number">—</strong> <button type="button" class="btn-copy-number" data-target="jib-account-number" style="margin-right:8px; padding:3px 10px; font-size:12px; border-radius:6px; border:1px solid var(--gold); background:transparent; color:var(--gold); cursor:pointer;">نسخ</button></div>
            </div>
          </label>
          <label class="payment-option" data-method="kareemi">
            <input type="radio" name="paymentMethod" value="kareemi">
            <div style="width:100%;">
              <div class="pm-title">كريمي Kareemi</div>
                              <div class="row"><span>اسم الحساب</span><strong id="kareemi-account-name">—</strong></div>
                <div class="row"><span>رقم الحساب</span><strong id="kareemi-account-number">—</strong> <button type="button" class="btn-copy-number" data-target="kareemi-account-number" style="margin-right:8px; padding:3px 10px; font-size:12px; border-radius:6px; border:1px solid var(--gold); background:transparent; color:var(--gold); cursor:pointer;">نسخ</button></div>
            </div>
          </label>
        </div>

        <div id="receipt-upload-wrap" style="display:none; margin-top:16px;">
          <div class="form-group"><label>رقم عملية الدفع</label><input type="text" name="transactionNumber"></div>
          <div class="form-group"><label>صورة إيصال الدفع</label><input type="file" name="receipt" accept="image/*"></div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:20px;">تأكيد الطلب</button>
      </form>

      <div class="cart-summary">
        <h3 style="margin-bottom:14px; color:var(--primary); font-family:var(--font-display);">ملخص الطلب</h3>
        <div id="checkout-items"></div>
        <div id="checkout-totals" style="margin-top:10px;"></div>
        <button class="btn btn-whatsapp btn-block" id="wa-send-cart" style="margin-top:14px;">إرسال السلة عبر واتساب</button>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script src="/js/checkout.js"></script>
<script>document.addEventListener('DOMContentLoaded', () => { renderHeader(''); renderFooter(); });</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Ammar\Desktop\diwan-alasala-laravel\resources\views/pages/checkout.blade.php ENDPATH**/ ?>