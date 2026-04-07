<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026 — FREELANCE DOMAIN MIGRATION
 * Биржа фриланса, специалисты, заказы, контракты, эскроу, арбитраж.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Фрилансеры (профили специалистов)
        if (!Schema::hasTable('freelancers')) {
            Schema::create('freelancers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('full_name')->index();
                $table->string('specialization')->index();
                $table->text('bio')->nullable();
                $table->decimal('hourly_rate_kopecks', 15, 0)->default(0);
                $table->integer('experience_years')->default(0);
                $table->jsonb('skills')->nullable(); // ['PHP', 'Laravel', 'Vue']
                $table->jsonb('languages')->nullable(); // ['ru', 'en']
                $table->float('rating')->default(0);
                $table->integer('completed_orders_count')->default(0);
                $table->enum('status', ['active', 'busy', 'offline', 'banned'])->default('active');
                $table->boolean('is_verified')->default(false);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Профили фрилансеров на бирже');
            });
        }

        // 2. Услуги (предложения фрилансеров)
        if (!Schema::hasTable('freelance_service_offers')) {
            Schema::create('freelance_service_offers', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('freelancer_id')->constrained('freelancers')->onDelete('cascade');
                $table->string('title')->index();
                $table->text('description');
                $table->decimal('price_kopecks', 15, 0);
                $table->integer('delivery_days')->default(3);
                $table->jsonb('package_details')->nullable(); // ['basic' => [...], 'standard' => [...]]
                $table->boolean('is_active')->default(true);
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Конкретные услуги, предлагаемые специалистами');
            });
        }

        // 3. Заказы (сделки между клиентом и фрилансером)
        if (!Schema::hasTable('freelance_orders')) {
            Schema::create('freelance_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('client_id')->constrained('users');
                $table->foreignId('freelancer_id')->constrained('freelancers');
                $table->foreignId('offer_id')->nullable()->constrained('freelance_service_offers');
                $table->string('title')->index();
                $table->text('requirements');
                $table->decimal('budget_kopecks', 15, 0);
                $table->decimal('commission_kopecks', 15, 0)->default(0);
                $table->enum('status', ['pending', 'escrow_hold', 'in_progress', 'review', 'completed', 'disputed', 'cancelled'])->default('pending');
                $table->dateTime('deadline_at')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->boolean('is_b2b')->default(false); // B2B vs B2C режим
                $table->foreignId('business_group_id')->nullable()->index();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Заказы на услуги фрилансеров');
            });
        }

        // 4. Контракты (юридическая и эскроу-часть сделки)
        if (!Schema::hasTable('freelance_contracts')) {
            Schema::create('freelance_contracts', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('order_id')->constrained('freelance_orders')->onDelete('cascade');
                $table->string('contract_number')->unique();
                $table->jsonb('legal_details')->nullable(); // ИНН, реквизиты сторон
                $table->decimal('escrow_amount_kopecks', 15, 0);
                $table->enum('escrow_status', ['awaiting', 'held', 'released', 'refunded', 'disputed_hold'])->default('awaiting');
                $table->text('arbitration_comment')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Официальные контракты для B2B и безопасных сделок');
            });
        }

        // 5. Портфолио (кейсы фрилансеров)
        if (!Schema::hasTable('freelance_portfolios')) {
            Schema::create('freelance_portfolios', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('freelancer_id')->constrained('freelancers')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->jsonb('media_urls')->nullable(); // Ссылки на фото/видео работы
                $table->string('case_url')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Работы в портфолио фрилансеров');
            });
        }

        // 6. Отзывы (рейтинг)
        if (!Schema::hasTable('freelance_reviews')) {
            Schema::create('freelance_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('order_id')->constrained('freelance_orders')->onDelete('cascade');
                $table->foreignId('reviewer_id')->constrained('users');
                $table->foreignId('freelancer_id')->constrained('freelancers');
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('metrics')->nullable(); // ['quality' => 5, 'speed' => 4, 'communication' => 5]
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Отзывы клиентов о работе фрилансеров');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('freelance_reviews');
        Schema::dropIfExists('freelance_portfolios');
        Schema::dropIfExists('freelance_contracts');
        Schema::dropIfExists('freelance_orders');
        Schema::dropIfExists('freelance_service_offers');
        Schema::dropIfExists('freelancers');
    }
};


