<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для Раздела 4: Агро (Фермерство), HR и Бизнес-Центры
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Агро-фермы
        if (!Schema::hasTable('agro_farms')) {
            Schema::create('agro_farms', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name');
                $table->string('inn')->unique();
                $table->string('address')->nullable();
                $table->jsonb('specialization')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Агро-предприятия и фермерские хозяйства');
            });
        }

        // 2. Агро-продукция
        if (!Schema::hasTable('agro_products')) {
            Schema::create('agro_products', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('farm_id')->index();
                $table->string('name');
                $table->string('sku')->index();
                $table->string('category')->index();
                $table->bigInteger('price_cents');
                $table->string('unit'); // kg, ton, l
                $table->float('current_stock')->default(0);
                $table->float('min_stock_alert')->default(1);
                $table->jsonb('properties')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('Продукция АПК');
            });
        }

        // 3. HR-вакансии
        if (!Schema::hasTable('hr_vacancies')) {
            Schema::create('hr_vacancies', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->string('title');
                $table->text('description');
                $table->jsonb('requirements')->nullable();
                $table->bigInteger('salary_min')->nullable();
                $table->bigInteger('salary_max')->nullable();
                $table->string('currency')->default('RUB');
                $table->string('status')->index(); // open, closed, draft, filled
                $table->string('location')->nullable();
                $table->boolean('remote_allowed')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('HR-вакансии предприятий');
            });
        }

        // 4. HR-отклики
        if (!Schema::hasTable('hr_applications')) {
            Schema::create('hr_applications', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('vacancy_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('resume_url')->nullable();
                $table->text('cover_letter')->nullable();
                $table->string('status')->index(); // pending, review, interview, rejected, hired
                $table->dateTime('interview_at')->nullable();
                $table->text('notes')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->comment('HR-отклики кандидатов');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_applications');
        Schema::dropIfExists('hr_vacancies');
        Schema::dropIfExists('agro_products');
        Schema::dropIfExists('agro_farms');
    }
};
