<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для Раздела 5: Недвижимость и Строительство (КАНОН 2026)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Недвижимость (проверка существующей таблицы, если была в другой миграции)
        if (!Schema::hasTable('re_properties')) {
            Schema::create('re_properties', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('type', ['apartment', 'house', 'land', 'commercial'])->index();
                $table->string('address');
                $table->jsonb('geo_point')->nullable(); // Point jsonb [lat, lng]
                $table->float('area')->nullable();
                $table->integer('rooms')->default(1);
                $table->integer('floor')->nullable();
                $table->bigInteger('price_cents');
                $table->string('status')->index(); // active, sold, rented, pending
                $table->jsonb('features')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Объекты недвижимости');
            });
        }

        // 2. Строительные проекты
        if (!Schema::hasTable('const_projects')) {
            Schema::create('const_projects', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->index(); // planning, active, halted, completed
                $table->bigInteger('estimated_cost')->default(0);
                $table->bigInteger('actual_cost')->default(0);
                $table->dateTime('deadline_at')->nullable();
                $table->string('address')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Строительные проекты и сметы');
            });
        }

        // 3. Строительные материалы (ресурсы проекта)
        if (!Schema::hasTable('const_materials')) {
            Schema::create('const_materials', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('project_id')->index();
                $table->string('name');
                $table->string('sku')->index();
                $table->float('quantity')->default(0);
                $table->string('unit'); // m3, ton, kg, piece
                $table->bigInteger('unit_price_cents')->default(0);
                $table->float('actual_usage')->default(0);
                $table->unsignedBigInteger('supplier_id')->nullable()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Материалы и ресурсы строительных проектов');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('const_materials');
        Schema::dropIfExists('const_projects');
        Schema::dropIfExists('re_properties');
    }
};
