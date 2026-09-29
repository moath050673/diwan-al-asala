<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * مكان حفظ صور المنتجات قابل للتغيير عبر PRODUCT_IMAGES_DISK:
 * - محليًا: القرص public (رابط نسبي /storage/...)
 * - على الاستضافة السحابية (Laravel Cloud): قرص تخزين سحابي (S3/R2) لأن ملفات السيرفر نفسه تُحذف مع كل نشر.
 */
class ProductImageStorage
{
    public function disk(): string
    {
        return (string) config('store.disks.images', 'public');
    }

    /** يحفظ الصورة ويعيد الرابط الذي يُخزَّن في product_images.image_url */
    public function store(UploadedFile $file): string
    {
        // بدون visibility لكل ملف: Laravel Cloud (Cloudflare R2) يرفضها — الظهور العام يُحدَّد عند إنشاء الـ Bucket
        $path = $file->store('products', $this->disk());

        // على القرص المحلي نُبقي الرابط نسبيًا ليعمل من أي عنوان (localhost، IP الجوال، الدومين)
        return $this->disk() === 'public' ? '/storage/'.$path : Storage::disk($this->disk())->url($path);
    }

    public function delete(string $url): void
    {
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
}
