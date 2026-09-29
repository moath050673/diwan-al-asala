<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * يعرض الملفات العامة المخزنة في القرص db_public (صور المنتجات) عبر /media/{path}.
 * أسماء الملفات عشوائية وفريدة، لذلك يمكن للمتصفح تخزينها مؤقتًا لمدة طويلة.
 */
class MediaController extends Controller
{
    public function show(string $path)
    {
        // رفض أي محاولة للخروج من المجلد (..) قبل الوصول للتخزين
        abort_if(str_contains($path, '..'), 404);

        $disk = Storage::disk('db_public');
        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
