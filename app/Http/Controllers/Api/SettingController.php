<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    private array $publicKeys = ['store_name', 'store_currency', 'whatsapp_number', 'facebook_url', 'instagram_url', 'shipping_cost_sanaa'];

    public function public()
    {
        $data = Setting::whereIn('setting_key', $this->publicKeys)->pluck('setting_value', 'setting_key');
        return response()->json(['success' => true, 'data' => $data]);
    }

    // ---------- Admin only ----------
    public function all()
    {
        $data = Setting::pluck('setting_value', 'setting_key');
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function update(Request $request)
    {
        $updates = $request->all();
        if (empty($updates)) {
            return response()->json(['success' => false, 'message' => 'لا توجد إعدادات لتحديثها'], 400);
        }
        foreach ($updates as $key => $value) {
            Setting::set($key, $value);
        }
        return response()->json(['success' => true, 'message' => 'تم تحديث الإعدادات']);
    }
}
