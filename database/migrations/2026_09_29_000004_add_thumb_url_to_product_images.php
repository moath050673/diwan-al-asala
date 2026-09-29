<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نسخة مصغّرة محسّنة لكل صورة منتج (تُستخدم في بطاقات المنتجات لتسريع التحميل).
 * الصور القديمة بدون نسخة مصغّرة تبقى تعمل بالصورة الأصلية.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->string('thumb_url')->nullable()->after('image_url');
            $table->index(['product_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'sort_order']);
            $table->dropColumn('thumb_url');
        });
    }
};
