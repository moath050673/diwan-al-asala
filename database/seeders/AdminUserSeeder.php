<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ينشئ مستخدم مدير أولي باستخدام بيانات ADMIN_EMAIL / ADMIN_PASSWORD من .env
 * يُشغَّل يدويًا مرة واحدة فقط:
 *   php artisan db:seed --class=AdminUserSeeder
 * يجبر المستخدم على تغيير كلمة المرور عند أول تسجيل دخول (must_change_password = true)
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('store.admin.email');
        $password = config('store.admin.password');

        if (!$email || !$password) {
            $this->command->error('يرجى تعبئة ADMIN_EMAIL و ADMIN_PASSWORD في ملف .env أولًا.');
            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command->info('يوجد مستخدم بهذا البريد الإلكتروني مسبقًا.');
            return;
        }

        User::create([
            'name' => config('store.admin.name'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'status' => 'active',
            'must_change_password' => true,
        ]);

        $this->command->info('✔ تم إنشاء حساب المدير بنجاح. سيُطلب تغيير كلمة المرور عند أول تسجيل دخول.');
    }
}
