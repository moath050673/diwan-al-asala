<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;

class SettingController extends Controller
{
    public function public()
    {
        $data = array_filter(Setting::many(Setting::PUBLIC_KEYS), fn ($v) => $v !== null);
        return response()->json(['success' => true, 'data' => $data]);
    }

    // ---------- Admin only ----------
    public function all()
    {
        return response()->json(['success' => true, 'data' => Setting::allCached()]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        foreach ($request->validated() as $key => $value) {
            Setting::set($key, is_bool($value) ? (int) $value : $value);
        }
        return response()->json(['success' => true, 'message' => 'تم تحديث الإعدادات']);
    }
}
