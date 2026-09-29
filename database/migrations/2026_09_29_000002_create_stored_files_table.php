<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ملفات مخزنة داخل قاعدة البيانات (قرص Laravel بالمُشغّل "database").
 * يُستخدم على استضافة بدون تخزين دائم للملفات (Laravel Cloud Starter).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stored_files', function (Blueprint $table) {
            $table->id();
            $table->string('bucket', 50);          // public / private
            $table->string('path', 255);
            $table->binary('contents');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('visibility', 10)->default('private');
            $table->timestamps();

            $table->unique(['bucket', 'path']);
        });

        // BLOB في MySQL حده 64KB فقط — الصور تحتاج LONGBLOB
        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('ALTER TABLE stored_files MODIFY contents LONGBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stored_files');
    }
};
