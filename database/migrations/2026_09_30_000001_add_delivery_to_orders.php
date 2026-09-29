<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * خدمة التوصيل اختيارية: false = استلام من المتجر (بدون تكلفة توصيل).
 * الطلبات السابقة كلها كانت بتوصيل، لذلك القيمة الافتراضية true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('delivery')->default(true)->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery');
        });
    }
};
