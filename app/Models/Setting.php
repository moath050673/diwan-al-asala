<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;
    protected $fillable = ['setting_key', 'setting_value'];

    private const CACHE_KEY = 'settings.all';

    /** المفاتيح التي يسمح للوحة التحكم بتعديلها (أي مفتاح آخر يُرفض) */
    public const EDITABLE_KEYS = [
        'store_name', 'store_currency', 'whatsapp_number', 'facebook_url', 'instagram_url', 'shipping_cost_sanaa',
        'jib_enabled', 'jib_account_name', 'jib_account_number',
        'kareemi_enabled', 'kareemi_account_name', 'kareemi_account_number',
        'cod_enabled',
    ];

    /** المفاتيح المعروضة للزوار عبر /api/settings */
    public const PUBLIC_KEYS = ['store_name', 'store_currency', 'whatsapp_number', 'facebook_url', 'instagram_url', 'shipping_cost_sanaa'];

    protected static function booted()
    {
        static::saving(function ($setting) {
            $setting->updated_at = now();
        });

        // أي تعديل على الإعدادات يمسح النسخة المخزنة مؤقتًا
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** كل الإعدادات كمصفوفة key => value (استعلام واحد، مخزن مؤقتًا) */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::pluck('setting_value', 'setting_key')->all());
    }

    public static function get(string $key, $default = null)
    {
        return static::allCached()[$key] ?? $default;
    }

    /** مجموعة إعدادات محددة بترتيبها (القيم المفقودة = null) */
    public static function many(array $keys): array
    {
        $all = static::allCached();

        return collect($keys)->mapWithKeys(fn ($k) => [$k => $all[$k] ?? null])->all();
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['setting_key' => $key], ['setting_value' => $value === null ? null : (string) $value]);
    }
}
