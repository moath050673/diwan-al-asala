<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('sku', 60)->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('old_price', 12, 2)->nullable();
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('weight', 50)->nullable();
            $table->string('size', 50)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('featured')->default(false);
            $table->timestamps();

            $table->index('status');
            // Fulltext index is MySQL-only; skip on SQLite (used in tests) to avoid RuntimeException.
            if (DB::connection()->getDriverName() === 'mysql') {
                $table->fullText(['name', 'description', 'sku']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
