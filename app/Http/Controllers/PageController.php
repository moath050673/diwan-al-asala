<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * PageController — يعرض صفحات الواجهة الأمامية (Blade views).
 * البيانات الفعلية (منتجات، تصنيفات...) تُجلب من نفس الجهاز عبر JavaScript (routes/api.php)
 * تمامًا كما في نسخة Node.js، للحفاظ على تجربة استخدام متطابقة (فلترة بدون إعادة تحميل، إلخ).
 * صفحة المنتج تُحمّل بيانات SEO (العنوان، الوصف، الصورة، Schema.org) من الخادم لمحركات البحث.
 */
class PageController extends Controller
{
    public function home()
    {
        return view('pages.home', ['storeSchema' => $this->storeSchema()]);
    }

    public function products() { return view('pages.products'); }

    public function product(int $id)
    {
        $product = Product::active()->with(['category', 'images'])->find($id);
        // منتج غير موجود/مخفي: 404 حقيقي (بدل صفحة فارغة تُفهرس في محركات البحث)
        abort_unless($product, 404);

        $image = $product->images->first();
        $imageUrl = $image ? url(ProductImage::resolveUrl($image->image_url)) : null;
        $description = Str::limit(Str::squish(strip_tags((string) $product->description)), 155)
            ?: "{$product->name} من متجر ديوان الأصالة — {$product->category->name}، توصيل داخل صنعاء.";

        return view('pages.product', [
            'id' => $id,
            'product' => $product,
            'seoDescription' => $description,
            'seoImage' => $imageUrl,
            'productSchema' => $this->productSchema($product, $description, $imageUrl),
        ]);
    }

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

    // ---------- Schema.org (Structured Data) ----------

    private function productSchema(Product $product, string $description, ?string $imageUrl): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $description,
            'sku' => $product->sku,
            'image' => $product->images->map(fn ($img) => url(ProductImage::resolveUrl($img->image_url)))->values()->all() ?: null,
            'category' => $product->category->name,
            'brand' => ['@type' => 'Brand', 'name' => 'ديوان الأصالة'],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product', $product->id),
                'priceCurrency' => 'YER',
                'price' => (string) (float) $product->price,
                'availability' => $product->stock_quantity > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ]);
    }

    private function storeSchema(): array
    {
        try {
            $settings = Setting::many(['store_name', 'whatsapp_number', 'facebook_url', 'instagram_url']);
        } catch (\Throwable $e) {
            report($e);
            $settings = array_fill_keys(['store_name', 'whatsapp_number', 'facebook_url', 'instagram_url'], null);
        }

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Store',
            'name' => $settings['store_name'] ?: 'متجر ديوان الأصالة',
            'url' => url('/'),
            'logo' => url('/img/logo.png'),
            'image' => url('/img/logo.png'),
            'telephone' => $settings['whatsapp_number'] ? '+'.ltrim($settings['whatsapp_number'], '+') : null,
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'صنعاء', 'addressCountry' => 'YE'],
            'currenciesAccepted' => 'YER',
            'sameAs' => array_values(array_filter([$settings['facebook_url'], $settings['instagram_url']],
                fn ($u) => $u && !in_array(rtrim($u, '/'), ['https://facebook.com', 'https://instagram.com'], true))) ?: null,
        ]);
    }
}
