/* ===================================================================
   api.js — طبقة الاتصال بالـ Backend (REST API)
   في حال تعذّر الوصول للـ API (مثلاً أثناء التطوير بدون سيرفر)
   يتم الرجوع تلقائيًا إلى بيانات محلية تجريبية (Seed Data) من products.js
   =================================================================== */

const API = (() => {
  // غيّر هذا الرابط ليطابق عنوان الـ Backend الفعلي عند النشر
  const BASE_URL = window.DIWAN_API_BASE || '/api';

  async function request(path, options = {}) {
    try {
      const isFormData = options.body instanceof FormData;
      const headers = isFormData
        ? { ...(options.headers || {}) } // اتركها فارغة — المتصفح يضيف Content-Type الصحيح تلقائيًا مع حدود الملف (boundary)
        : { 'Content-Type': 'application/json', ...(options.headers || {}) };
      const res = await fetch(BASE_URL + path, { headers, ...options });
      if (!res.ok) throw new Error('API_ERROR_' + res.status);
      return await res.json();
    } catch (err) {
      console.warn('[API] تعذّر الاتصال بالـ Backend، سيتم استخدام البيانات التجريبية المحلية:', err.message);
      return null; // يتيح للاستدعاء الرجوع إلى fallback محلي
    }
  }

  return {
    getProducts: (params = '') => request('/products' + params),
    getProduct: (id) => request('/products/' + id),
    getCategories: () => request('/categories'),
    createOrder: (payload) => request('/orders', { method: 'POST', body: payload instanceof FormData ? payload : JSON.stringify(payload) }),
    getSettings: () => request('/settings'),
  };
})();
