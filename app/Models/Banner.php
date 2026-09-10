<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    public $timestamps = false;
    protected $fillable = ['title', 'description', 'image', 'button_text', 'button_url', 'status', 'sort_order'];

    protected static function booted()
    {
        static::creating(function ($banner) {
            $banner->created_at = now();
        });
    }
}
