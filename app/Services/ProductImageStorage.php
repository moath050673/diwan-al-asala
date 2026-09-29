<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * مكان حفظ صور المنتجات قابل للتغيير عبر PRODUCT_IMAGES_DISK:
 * - محليًا: القرص public (رابط نسبي /storage/...)
 * - Laravel Cloud Starter: db_public (داخل قاعدة البيانات، تُعرض عبر /media/...)
 * - لاحقًا: قرص Bucket/S3 — بدون أي تعديل في الكود
 *
 * كل صورة تُحسَّن تلقائيًا (ImageOptimizer): نسخة رئيسية بحد أقصى 1600px ونسخة مصغّرة
 * 600px لبطاقات المنتجات، بصيغة WebP عند توفرها.
 */
class ProductImageStorage
{
    public function __construct(private ImageOptimizer $optimizer) {}

    public function disk(): string
    {
        return (string) config('store.disks.images', 'public');
    }

    /**
     * يحفظ الصورة ويعيد ['image_url' => ..., 'thumb_url' => ...|null] لتُخزَّن في product_images.
     */
    public function store(UploadedFile $file): array
    {
        $variants = $this->optimizer->variants($file->getRealPath(), [
            'main' => (int) config('store.images.max_dimension', 1600),
            'thumb' => (int) config('store.images.thumb_dimension', 600),
        ], (int) config('store.images.quality', 82));

        // بدون GD (أو صورة ضخمة جدًا): نحفظ الملف الأصلي كما هو
        if (!$variants) {
            return ['image_url' => $this->url($file->store('products', $this->disk())), 'thumb_url' => null];
        }

        // بدون visibility لكل ملف: Cloudflare R2 يرفضها — الظهور العام يُحدَّد عند إنشاء الـ Bucket
        $name = Str::random(40);
        $main = "products/{$name}.{$variants['main']['extension']}";
        $thumb = "products/thumbs/{$name}.{$variants['thumb']['extension']}";

        $disk = Storage::disk($this->disk());
        $disk->put($main, $variants['main']['contents']);
        $disk->put($thumb, $variants['thumb']['contents']);

        return ['image_url' => $this->url($main), 'thumb_url' => $this->url($thumb)];
    }

    public function delete(?string $url): void
    {
        if (!$url) {
            return;
        }

        // القرص الحالي أولًا، ثم القرص المحلي للصور القديمة المرفوعة قبل الانتقال للتخزين السحابي
        if ($this->disk() !== 'public') {
            $base = rtrim(Storage::disk($this->disk())->url(''), '/').'/';
            if (str_starts_with($url, $base)) {
                Storage::disk($this->disk())->delete(Str::after($url, $base));
                return;
            }
        }

        if (str_starts_with($url, '/storage/')) {
            Storage::disk('public')->delete(Str::after($url, '/storage/'));
        }
        // رابط خارجي أُدخل يدويًا — لا نحذف شيئًا
    }

    private function url(string $path): string
    {
        // على القرص المحلي نُبقي الرابط نسبيًا ليعمل من أي عنوان (localhost، IP الجوال، الدومين)
        return $this->disk() === 'public' ? '/storage/'.$path : Storage::disk($this->disk())->url($path);
    }
}
