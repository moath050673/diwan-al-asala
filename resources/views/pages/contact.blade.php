@extends('layouts.app')
@section('title', 'تواصل معنا | متجر ديوان الأصالة')
@section('description', 'تواصل مع متجر ديوان الأصالة عبر واتساب أو نموذج التواصل للاستفسار عن المنتجات والطلبات والتوصيل داخل صنعاء.')
@section('content')
<div class="page-header"><div class="container"><h1>تواصل معنا</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / تواصل معنا</div></div></div>
<div class="section">
  <div class="container split-layout split-contact">
    <div style="background:var(--white); border-radius:var(--radius-lg); padding:30px; box-shadow:var(--shadow-sm);">
      <h3 style="color:var(--primary); font-family:var(--font-display); margin-bottom:16px;">معلومات التواصل</h3>
      <p style="margin-bottom:10px;">📍 صنعاء، اليمن</p>
      <p style="margin-bottom:10px;">🕘 يوميًا من 9 صباحًا حتى 10 مساءً</p>
      <p style="margin-bottom:10px;"><a href="#" id="contact-wa">💬 تواصل عبر واتساب</a></p>
      <p style="margin-bottom:10px;" hidden><a href="#" id="contact-fb" target="_blank" rel="noopener">📘 صفحتنا على فيسبوك</a></p>
      <p style="margin-bottom:10px;" hidden><a href="#" id="contact-ig" target="_blank" rel="noopener">📷 صفحتنا على انستقرام</a></p>
    </div>
    <form id="contact-form" style="background:var(--white); border-radius:var(--radius-lg); padding:30px; box-shadow:var(--shadow-sm);">
      <div class="form-group"><label for="ct-name">الاسم <span class="req">*</span></label><input type="text" name="name" id="ct-name" required maxlength="150" autocomplete="name"></div>
      <div class="form-group"><label for="ct-phone">رقم الهاتف <span class="req">*</span></label><input type="tel" name="phone" id="ct-phone" required maxlength="30" inputmode="tel" autocomplete="tel"></div>
      <div class="form-group"><label for="ct-message">الرسالة <span class="req">*</span></label><textarea name="message" id="ct-message" rows="4" required maxlength="5000"></textarea></div>
      <button type="submit" class="btn btn-primary btn-block">إرسال الرسالة</button>
    </form>
  </div>
</div>
@endsection
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    renderHeader('contact');
    renderFooter();
    document.getElementById('contact-wa').href = WhatsAppLink.general();
    // روابط التواصل الاجتماعي تظهر فقط بعد ضبطها من لوحة التحكم
    [['contact-fb', window.DIWAN_SETTINGS.facebookUrl], ['contact-ig', window.DIWAN_SETTINGS.instagramUrl]].forEach(([id, url]) => {
      if (!isSocialConfigured(url)) return;
      const link = document.getElementById(id);
      link.href = url;
      link.parentElement.hidden = false;
    });

    document.getElementById('contact-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = e.target.querySelector('button[type=submit]');
      if (btn.disabled) return; // منع الإرسال المزدوج
      btn.disabled = true;
      const fd = new FormData(e.target);
      const result = await API.sendContact({ name: fd.get('name'), phone: fd.get('phone'), message: fd.get('message') });
      btn.disabled = false;
      if (!result.ok) {
        showToast(API.errorMessage(result, 'تعذّر إرسال رسالتك، يرجى المحاولة مجددًا أو التواصل عبر واتساب.'), 'error');
        return;
      }
      showToast('تم إرسال رسالتك بنجاح، سنتواصل معك قريبًا', 'success');
      e.target.reset();
    });
  });
</script>
@endpush
