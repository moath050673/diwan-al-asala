<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فهارس إضافية (غير مدمّرة) لتسريع:
 * - ترتيب الطلبات والعملاء حسب التاريخ في لوحة التحكم
 * - إحصائيات آخر 14 يوم في لوحة القيادة
 * - ترتيب المنتجات النشطة حسب الأحدث في المتجر
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->index('created_at');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });
    }
};
