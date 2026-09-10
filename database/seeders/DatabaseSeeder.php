<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            // ملاحظة: AdminUserSeeder لا يُستدعى هنا تلقائيًا؛ شغّله يدويًا بأمر منفصل
            // بعد تعبئة ADMIN_EMAIL / ADMIN_PASSWORD في .env:
            // php artisan db:seed --class=AdminUserSeeder
        ]);
    }
}
