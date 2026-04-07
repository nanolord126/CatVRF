<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('beauty_products')) {
            return;
        }

        Schema::create('beauty_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->foreignId('beauty_salon_id')->constrained('beauty_salons')->onDelete('cascade');

            $table->string('name')->comment('Название товара');
            $table->string('sku')->nullable()->unique()->comment('Артикул (SKU)');
            $table->text('description')->nullable()->comment('Описание товара');
            $table->integer('price')->comment('Цена в копейках');
            
            $table->integer('current_stock')->default(0)->comment('Текущий остаток на складе');

            $table->jsonb('tags')->nullable()->comment('Теги для фильтрации и аналитики');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Товары для продажи в салонах красоты');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('beauty_products');
    }
};


