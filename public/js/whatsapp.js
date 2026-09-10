/* ===================================================================
   whatsapp.js — بناء روابط واتساب بشكل آمن ومنظم
   رقم المتجر يُقرأ من الإعدادات (window.DIWAN_SETTINGS.whatsappNumber)
   ويجب ألا يكون مكتوبًا بشكل ثابت هنا. القيمة الحالية Placeholder فقط.
   =================================================================== */

const WhatsAppLink = (() => {
  // Placeholder — يجب استبداله من لوحة التحكم / إعدادات المتجر عبر الـ API
  function getStoreNumber() {
    return (window.DIWAN_SETTINGS && window.DIWAN_SETTINGS.whatsappNumber) || '967700000000';
  }

  function buildLink(message) {
    const number = getStoreNumber().replace(/\D/g, '');
    return `https://wa.me/${number}?text=${encodeURIComponent(message)}`;
  }

  function general() {
    return buildLink('السلام عليكم، أرغب بالاستفسار عن منتجاتكم في متجر ديوان الأصالة.');
  }

  function productInquiry(product) {
    const msg = [
      'السلام عليكم،',
      'أرغب بالاستفسار عن المنتج التالي من متجر ديوان الأصالة:',
      '',
      `المنتج: ${product.name}`,
      `السعر: ${Products.formatPrice(product.price)}`,
    ].join('\n');
    return buildLink(msg);
  }

  function cartSummary(items, totals) {
    const lines = ['السلام عليكم،', 'لدي طلب من متجر ديوان الأصالة، تفاصيل السلة:', ''];
    items.forEach(i => lines.push(`- ${i.name} × ${i.qty} = ${Products.formatPrice(i.price * i.qty)}`));
    lines.push('');
    lines.push(`الإجمالي: ${Products.formatPrice(totals.total)}`);
    return buildLink(lines.join('\n'));
  }

  function orderConfirmation(order) {
    const lines = [
      'السلام عليكم،',
      'لدي طلب جديد من متجر ديوان الأصالة.',
      '',
      `رقم الطلب: #${order.orderNumber}`,
      '',
      'المنتجات:',
      ...order.items.map(i => `- ${i.name} × ${i.qty}`),
      '',
      `الإجمالي: ${Products.formatPrice(order.total)}`,
      '',
      `اسم العميل: ${order.customerName}`,
      `رقم الهاتف: ${order.customerPhone}`,
      `العنوان: ${order.customerAddress}`,
      `طريقة الدفع: ${order.paymentMethodLabel}`,
    ];
    return buildLink(lines.join('\n'));
  }

  return { general, productInquiry, cartSummary, orderConfirmation, getStoreNumber };
})();
