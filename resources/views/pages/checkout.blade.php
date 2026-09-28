@extends('layouts.app')
@section('title', 'إتمام الطلب | متجر ديوان الأصالة')
@section('content')
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

    <div id="checkout-content" class="split-layout split-checkout">
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
          @if(($payment['cod_enabled'] ?? '1') !== '0')
          <label class="payment-option selected" data-method="cod">
            <input type="radio" name="paymentMethod" value="cod" checked>
            <div class="pm-body"><div class="pm-title">الدفع عند الاستلام</div><div class="pm-desc">ادفع نقدًا عند استلام طلبك، لا حاجة لأي إيصال.</div></div>
          </label>
          @endif
          @if(($payment['jib_enabled'] ?? '1') !== '0')
          <label class="payment-option" data-method="jib">
            <input type="radio" name="paymentMethod" value="jib">
            <div class="pm-body">
              <div class="pm-title">جيب JIB</div>
              <div class="pm-desc">حوّل المبلغ إلى الحساب التالي ثم أرفق صورة الإيصال.</div>
              <div class="pm-account">
                <div class="pm-field">
                  <span class="pm-label">اسم الحساب</span>
                  <strong class="pm-value" id="jib-account-name">{{ $payment['jib_account_name'] ?: 'غير متوفر حاليًا' }}</strong>
                </div>
                <div class="pm-field">
                  <span class="pm-label">رقم الحساب</span>
                  <div class="pm-value-row">
                    <strong class="pm-value pm-number" id="jib-account-number" dir="ltr">{{ $payment['jib_account_number'] ?: '—' }}</strong>
                    <button type="button" class="btn-copy-number" data-target="jib-account-number">نسخ الرقم</button>
                  </div>
                </div>
              </div>
            </div>
          </label>
          @endif
          @if(($payment['kareemi_enabled'] ?? '1') !== '0')
          <label class="payment-option" data-method="kareemi">
            <input type="radio" name="paymentMethod" value="kareemi">
            <div class="pm-body">
              <div class="pm-title">كريمي Kareemi</div>
              <div class="pm-desc">حوّل المبلغ إلى الحساب التالي ثم أرفق صورة الإيصال.</div>
              <div class="pm-account">
                <div class="pm-field">
                  <span class="pm-label">اسم الحساب</span>
                  <strong class="pm-value" id="kareemi-account-name">{{ $payment['kareemi_account_name'] ?: 'غير متوفر حاليًا' }}</strong>
                </div>
                <div class="pm-field">
                  <span class="pm-label">رقم الحساب</span>
                  <div class="pm-value-row">
                    <strong class="pm-value pm-number" id="kareemi-account-number" dir="ltr">{{ $payment['kareemi_account_number'] ?: '—' }}</strong>
                    <button type="button" class="btn-copy-number" data-target="kareemi-account-number">نسخ الرقم</button>
                  </div>
                </div>
              </div>
            </div>
          </label>
          @endif
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
@endsection
@push('scripts')
<script src="/js/checkout.js"></script>
<script>document.addEventListener('DOMContentLoaded', () => { renderHeader(''); renderFooter(); });</script>
@endpush