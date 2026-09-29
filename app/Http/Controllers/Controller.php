<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * عدد العناصر في الصفحة من ?limit= مع حد أدنى 1 وحد أقصى (config/store.php)
     * — يمنع طلب ?limit=1000000 الذي قد يُسقط الخادم.
     */
    protected function perPage(Request $request, int $default = 20): int
    {
        return max(1, min((int) $request->query('limit', $default), (int) config('store.max_per_page', 100)));
    }

    /**
     * قيمة نصية من الرابط (?q=...) مقصوصة لطول معقول، أو null.
     * ?q[]=x يصل كمصفوفة ويسبب خطأ 500 داخل where — نتجاهله.
     */
    protected function queryText(Request $request, string $key, int $max = 100): ?string
    {
        $value = $request->query($key);
        if (!is_string($value)) {
            return null;
        }
        $value = trim(mb_substr($value, 0, $max));

        return $value === '' ? null : $value;
    }
}
