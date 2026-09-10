<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    protected $fillable = ['setting_key', 'setting_value'];

    protected static function booted()
    {
        static::saving(function ($setting) {
            $setting->updated_at = now();
        });
    }

    public static function get(string $key, $default = null)
    {
        return static::where('setting_key', $key)->value('setting_value') ?? $default;
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['setting_key' => $key], ['setting_value' => (string) $value]);
    }
}
