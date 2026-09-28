<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * متى أُعيدت كميات الطلب إلى المخزون (عند الإلغاء أو الحذف).
 * يمنع إرجاع نفس الكمية مرتين، ويسمح بخصمها مجددًا إذا أُعيد تفعيل طلب ملغي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_released_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_released_at');
        });
    }
};
