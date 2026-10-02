<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * موقع التوصيل المحدد من الخريطة (اختياري) — بديل أو مكمّل للعنوان المكتوب.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('location_lat', 10, 7)->nullable()->after('customer_address');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['location_lat', 'location_lng']);
        });
    }
};
