<?php

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
        // 1. Магазины (Одежда/Обувь/Детские товары)
        if (!Schema::hasTable('shop_products')) {
            Schema::create('shop_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('cascade');
                $table->string('name')->comment('Название товара');
                $table->string('sku')->index()->comment('Артикул');
                $table->string('category')->index()->comment('clothes, shoes, kids, etc');
                $table->integer('price_kopeks')->default(0);
                $table->integer('compare_at_price_kopeks')->nullable();
                $table->jsonb('attributes')->nullable()->comment('Размеры, цвета, материал');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Товары магазинов (Multi-tenant)');
            });
        }

        // 2. Фотостудии / Фотографы
        if (!Schema::hasTable('photo_studios')) {
            Schema::create('photo_studios', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Название студии / Имя фотографа');
                $table->text('bio')->nullable();
                $table->jsonb('equipment')->nullable()->comment('Список техники');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Фотостудии и профили фотографов');
            });
        }

        if (!Schema::hasTable('photo_sessions')) {
            Schema::create('photo_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('studio_id')->constrained('photo_studios')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                $table->dateTime('scheduled_at')->index();
                $table->integer('duration_minutes')->default(60);
                $table->string('status')->default('pending')->index();
                $table->integer('price_kopeks')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->comment('Бронирования фотосессий');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_sessions');
        Schema::dropIfExists('photo_studios');
        Schema::dropIfExists('shop_products');
    }
};
