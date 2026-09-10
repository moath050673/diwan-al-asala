<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'store_name' => env('STORE_NAME', 'متجر ديوان الأصالة'),
            'store_currency' => env('STORE_CURRENCY', 'ريال يمني'),
            'whatsapp_number' => env('WHATSAPP_NUMBER', '967700000000'),
            'facebook_url' => env('FACEBOOK_URL', 'https://facebook.com/'),
            'instagram_url' => env('INSTAGRAM_URL', 'https://instagram.com/'),
            'shipping_cost_sanaa' => env('SHIPPING_COST_SANAA', 1500),
            'jib_enabled' => '1',
            'jib_account_name' => env('JIB_ACCOUNT_NAME', 'متجر ديوان الأصالة'),
            'jib_account_number' => env('JIB_ACCOUNT_NUMBER', 'PLACEHOLDER'),
            'kareemi_enabled' => '1',
            'kareemi_account_name' => env('KAREEMI_ACCOUNT_NAME', 'متجر ديوان الأصالة'),
            'kareemi_account_number' => env('KAREEMI_ACCOUNT_NUMBER', 'PLACEHOLDER'),
            'cod_enabled' => '1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['setting_key' => $key], ['setting_value' => (string) $value]);
        }
    }
}
