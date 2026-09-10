<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'image_url', 'sort_order'];

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
}
