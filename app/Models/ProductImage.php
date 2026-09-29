<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'image_url', 'thumb_url', 'sort_order'];

    protected static function booted()
    {
        static::creating(function ($image) {
            $image->created_at = now();
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * يبني رابطًا صالحًا للاستخدام المباشر في الواجهة بغض النظر عن شكل القيمة المخزَّنة
     * (رابط مطلق، مسار يبدأ بـ / مثل /media أو /storage، أو اسم ملف نسبي أُدخل يدويًا).
     */
    public static function resolveUrl(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        if (str_starts_with($path, '/')) return $path;

        return '/storage/'.ltrim($path, '/');
    }
}
