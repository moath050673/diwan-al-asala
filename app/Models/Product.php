<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'description', 'price', 'old_price',
        'cost_price', 'stock_quantity', 'weight', 'size', 'status', 'featured',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'featured' => 'boolean',
        ];
    }

    protected static function booted()
    {
        // إضافة/تعديل/إخفاء منتج يُحدّث sitemap.xml فورًا (بدل انتظار انتهاء الكاش)
        static::saved(fn () => \App\Http\Controllers\SeoController::flushSitemap());
        static::deleted(fn () => \App\Http\Controllers\SeoController::flushSitemap());
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
