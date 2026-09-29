/* ===================================================================
   api.js — طبقة الاتصال بالـ Backend (REST API)
   request() تُرجع null عند الفشل، والصفحة تعرض رسالة خطأ مع إعادة المحاولة.
   =================================================================== */

/**
 * تهريب النصوص قبل إدراجها في innerHTML — يمنع XSS عند عرض بيانات قادمة
 * من المستخدمين أو من قاعدة البيانات (أسماء، عناوين، ملاحظات...).
 */
function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

const API = (() => {
  // غيّر هذا الرابط ليطابق عنوان الـ Backend الفعلي عند النشر
  const BASE_URL = window.DIWAN_API_BASE || '/api';

  function buildOptions(options) {
    const isFormData = options.body instanceof FormData;
    const headers = isFormData
      ? { Accept: 'application/json', ...(options.headers || {}) } // بدون Content-Type — المتصفح يضيفه مع حدود الملف (boundary)
      : { Accept: 'application/json', 'Content-Type': 'application/json', ...(options.headers || {}) };
    return { ...options, headers };
  }

  async function request(path, options = {}) {
    try {
      const res = await fetch(BASE_URL + path, buildOptions(options));
      if (!res.ok) throw new Error('API_ERROR_' + res.status);
      return await res.json();
    } catch (err) {
      console.warn('[API] تعذّر الاتصال بالخادم:', path, err.message);
      return null; // الصفحة تعرض رسالة خطأ مع زر إعادة المحاولة
    }
  }

  /**
   * لعمليات الكتابة (طلب، رسالة): لا يوجد fallback محلي — نُرجع النتيجة كما هي
   * { ok, status, data } حتى تعرض الصفحة رسالة الخطأ الحقيقية (مخزون، تحقق...).
   */
  async function submit(path, body) {
    try {
      const res = await fetch(BASE_URL + path, buildOptions({
        method: 'POST',
        body: body instanceof FormData ? body : JSON.stringify(body),
      }));
      const data = await res.json().catch(() => null);
      return { ok: res.ok, status: res.status, data };
    } catch (err) {
      return { ok: false, status: 0, data: null };
    }
  }

  /** يستخرج أول رسالة خطأ مفهومة من استجابة Laravel */
  function errorMessage(result, fallback) {
    const d = result && result.data;
    if (result && result.status === 429) return 'محاولات كثيرة، يرجى الانتظار دقيقة ثم المحاولة مجددًا.';
    if (d && d.errors) return Object.values(d.errors).flat()[0];
    return (d && d.message) || fallback;
  }

  return {
    getProducts: (params = '') => request('/products' + params),
    getProduct: (id) => request('/products/' + encodeURIComponent(id)),
    getCategories: () => request('/categories'),
    createOrder: (payload) => submit('/orders', payload),
    sendContact: (payload) => submit('/contact', payload),
    getSettings: () => request('/settings'),
    errorMessage,
  };
})();
