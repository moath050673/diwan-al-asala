<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * robots.txt و sitemap.xml ديناميكيان — يستخدمان عنوان الموقع الحقيقي (APP_URL/الدومين)
 * ويضيفان كل منتج نشط تلقائيًا عند إضافته من لوحة التحكم.
 */
class SeoController extends Controller
{
    private const STATIC_PAGES = [
        ['home', 'daily', '1.0'],
        ['products', 'daily', '0.9'],
        ['categories', 'weekly', '0.7'],
        ['about', 'monthly', '0.5'],
        ['contact', 'monthly', '0.5'],
        ['privacy', 'yearly', '0.2'],
        ['terms', 'yearly', '0.2'],
    ];

    private const VERSION_KEY = 'seo.sitemap.version';

    /** يُستدعى عند حفظ/حذف منتج — نسخة جديدة من المفتاح تجعل الكاش القديم غير مستخدم */
    public static function flushSitemap(): void
    {
        try {
            Cache::forever(self::VERSION_KEY, (string) microtime(true));
        } catch (\Throwable $e) {
            report($e); // الكاش غير متاح — لا يجب أن يمنع حفظ المنتج
        }
    }

    public function robots()
    {
        // لا نحجب /api: محركات البحث تحتاجه لعرض المنتجات (الصفحات تجلبها عبر JavaScript)
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /order-success',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        // مخزن مؤقتًا ساعة، ويُمسح فورًا عند أي تعديل على المنتجات (flushSitemap)
        $key = 'seo.sitemap.'.Cache::get(self::VERSION_KEY, '0').'.'.md5(url('/'));
        $xml = Cache::remember($key, 3600, function () {
            $urls = collect(self::STATIC_PAGES)->map(fn ($p) => [
                'loc' => route($p[0]), 'changefreq' => $p[1], 'priority' => $p[2], 'lastmod' => null,
            ]);

            Product::active()->select(['id', 'updated_at'])->orderBy('id')->each(function ($product) use ($urls) {
                $urls->push([
                    'loc' => route('product', $product->id),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                    'lastmod' => $product->updated_at?->toAtomString(),
                ]);
            });

            $body = $urls->map(fn ($u) => '  <url><loc>'.e($u['loc']).'</loc>'
                .($u['lastmod'] ? '<lastmod>'.$u['lastmod'].'</lastmod>' : '')
                ."<changefreq>{$u['changefreq']}</changefreq><priority>{$u['priority']}</priority></url>")->implode("\n");

            return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
                .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$body."\n</urlset>\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
