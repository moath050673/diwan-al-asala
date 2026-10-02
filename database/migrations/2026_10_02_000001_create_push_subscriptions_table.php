<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أجهزة المدير/الموظفين المسجّلة لاستقبال إشعارات الطلبات (Web Push) —
 * سجل لكل متصفح/جوال فعّل الإشعارات من لوحة التحكم.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('endpoint');                     // رابط خدمة الإشعارات (Google/Apple/Mozilla) — قد يتجاوز 255 حرفًا
            $table->char('endpoint_hash', 64)->unique();  // sha256 للرابط: فهرس فريد بدل فهرسة نص طويل
            $table->string('public_key', 255);            // p256dh
            $table->string('auth_token', 255);            // auth
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
