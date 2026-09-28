<?php

namespace App\Http\Controllers;

use App\Models\Setting;

/**
 * PageController — يعرض صفحات الواجهة الأمامية (Blade views).
 * البيانات الفعلية (منتجات، تصنيفات...) تُجلب من نفس الجهاز عبر JavaScript (routes/api.php)
 * تمامًا كما في نسخة Node.js، للحفاظ على تجربة استخدام متطابقة (فلترة بدون إعادة تحميل، إلخ).
 */
class PageController extends Controller
{
    public function home() { return view('pages.home'); }
    public function products() { return view('pages.products'); }
    public function product($id) { return view('pages.product', ['id' => $id]); }
    public function categories() { return view('pages.categories'); }
    public function cart() { return view('pages.cart'); }
    public function checkout()
    {
        // بيانات حسابات جيب/كريمي تُكتب مباشرة في الصفحة — لا تعتمد على طلب JavaScript منفصل
        $payment = Setting::many([
            'cod_enabled', 'jib_enabled', 'jib_account_name', 'jib_account_number',
            'kareemi_enabled', 'kareemi_account_name', 'kareemi_account_number',
        ]);

        return view('pages.checkout', ['payment' => $payment]);
    }
    public function orderSuccess() { return view('pages.order-success'); }
    public function about() { return view('pages.about'); }
    public function contact() { return view('pages.contact'); }
    public function privacy() { return view('pages.privacy'); }
    public function terms() { return view('pages.terms'); }

    // ---------- لوحة التحكم (Admin) ----------
    public function adminLogin() { return view('admin.login'); }
    public function adminDashboard() { return view('admin.dashboard'); }
    public function adminProducts() { return view('admin.products'); }
    public function adminOrders() { return view('admin.orders'); }
    public function adminCustomers() { return view('admin.customers'); }
    public function adminCategories() { return view('admin.categories'); }
    public function adminPayments() { return view('admin.payments'); }
    public function adminSettings() { return view('admin.settings'); }
}
