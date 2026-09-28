@extends('layouts.app')
@section('title', 'تواصل معنا | متجر ديوان الأصالة')
@section('content')
<div class="page-header"><div class="container"><h1>تواصل معنا</h1><div class="breadcrumb"><a href="/">الرئيسية</a> / تواصل معنا</div></div></div>
<div class="section">
  <div class="container split-layout split-contact">
    <div style="background:var(--white); border-radius:var(--radius-lg); padding:30px; box-shadow:var(--shadow-sm);">
      <h3 style="color:var(--primary); font-family:var(--font-display); margin-bottom:16px;">معلومات التواصل</h3>
      <p style="margin-bottom:10px;">📍 صنعاء، اليمن</p>
      <p style="margin-bottom:10px;">🕘 يوميًا من 9 صباحًا حتى 10 مساءً</p>
      <p style="margin-bottom:10px;"><a href="#" id="contact-wa">💬 تواصل عبر واتساب</a></p>
      <p style="margin-bottom:10px;"><a href="#" id="contact-fb">📘 صفحتنا على فيسبوك</a></p>
      <p style="margin-bottom:10px;"><a href="#" id="contact-ig">📷 صفحتنا على انستقرام</a></p>
    </div>
    <form id="contact-form" style="background:var(--white); border-radius:var(--radius-lg); padding:30px; box-shadow:var(--shadow-sm);">
      <div class="form-group"><label>الاسم <span class="req">*</span></label><input type="text" name="name" required></div>
      <div class="form-group"><label>رقم الهاتف <span class="req">*</span></label><input type="tel" name="phone" required></div>
      <div class="form-group"><label>الرسالة <span class="req">*</span></label><textarea name="message" rows="4" required></textarea></div>
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
    document.getElementById('contact-fb').href = window.DIWAN_SETTINGS.facebookUrl;
    document.getElementById('contact-ig').href = window.DIWAN_SETTINGS.instagramUrl;

    document.getElementById('contact-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(e.target);
      const result = await API.sendContact({ name: fd.get('name'), phone: fd.get('phone'), message: fd.get('message') });
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
