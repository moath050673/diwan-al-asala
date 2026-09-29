<?php

namespace App\Support;

/**
 * روابط الملفات الثابتة مع رقم نسخة (?v=وقت تعديل الملف).
 * Cloudflare والمتصفح يخزّنان CSS/JS مؤقتًا — الرقم يتغير مع كل نشر فيحصل الزائر
 * على النسخة الجديدة فورًا بدل نسخة قديمة من الذاكرة المؤقتة.
 */
class Asset
{
    private static array $versions = [];

    public static function url(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        if (!array_key_exists($path, self::$versions)) {
            $file = public_path(ltrim($path, '/'));
            self::$versions[$path] = is_file($file) ? (string) filemtime($file) : null;
        }

        return self::$versions[$path] ? $path.'?v='.self::$versions[$path] : $path;
    }
}
