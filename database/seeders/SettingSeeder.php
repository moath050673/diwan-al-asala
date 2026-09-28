<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'store_name' => config('store.name'),
            'store_currency' => config('store.currency'),
            'whatsapp_number' => config('store.whatsapp_number'),
            'facebook_url' => config('store.facebook_url'),
            'instagram_url' => config('store.instagram_url'),
            'shipping_cost_sanaa' => config('store.shipping_cost_sanaa'),
            'jib_enabled' => '1',
            'jib_account_name' => config('store.jib.account_name'),
            'jib_account_number' => config('store.jib.account_number'),
            'kareemi_enabled' => '1',
            'kareemi_account_name' => config('store.kareemi.account_name'),
            'kareemi_account_number' => config('store.kareemi.account_number'),
            'cod_enabled' => '1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['setting_key' => $key], ['setting_value' => (string) $value]);
        }
    }
}
