<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration EventPlanningService 2026.
 * Кодировка: UTF-8 без BOM, CRLF.
 * Канон: UUID, tenant_id, correlation_id, business_group_id, tags.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Основная таблица событий (Events)
        if (!Schema::hasTable('event_planning_events')) {
            Schema::create('event_planning_events', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('business_group_id')->nullable()->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('planner_id')->nullable()->index();
                
                $table->string('type')->index()->comment('wedding, corporate, birthday, anniversary, other');
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('event_date')->index();
                $table->string('location');
                $table->integer('guest_count')->unsigned()->default(1);
                
                $table->string('status')->default('draft')->index(); // draft, planning, confirmed, active, completed, cancelled, disputed
                $table->boolean('is_b2b')->default(false)->index();
                
                $table->integer('total_budget_kopecks')->unsigned()->default(0);
                $table->integer('prepayment_kopecks')->unsigned()->default(0);
                $table->integer('cancellation_fee_kopecks')->unsigned()->default(0);
                
                $table->jsonb('ai_plan')->nullable()->comment('Полный план, сгенерированный AI');
                $table->jsonb('cancellation_policy')->nullable()->comment('Правила отмены');
                $table->jsonb('tags')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('События и праздники — Канон 2026');
            });
        }

        // 2. Исполнители (Vendors) в рамках события
        if (!Schema::hasTable('event_planning_vendors')) {
            Schema::create('event_planning_vendors', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('event_id')->constrained('event_planning_events')->onDelete('cascade');
                $table->unsignedBigInteger('tenant_id')->index();
                
                $table->string('vertical')->index()->comment('food, photo, beauty, auto, decoration, music');
                $table->unsignedBigInteger('vendor_id')->comment('ID из соответствующей вертикали'); // ID сущности в другой вертикали
                $table->string('vendor_name');
                
                $table->string('status')->default('pending')->index(); // pending, approved, rejected, contracted, paid
                $table->integer('agreed_price_kopecks')->unsigned()->default(0);
                $table->integer('deposit_paid_kopecks')->unsigned()->default(0);
                
                $table->jsonb('agreed_conditions')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Связь событий с вендорами из других вертикалей');
            });
        }

        // 3. Бюджетная аналитика (Transactions/Budget Items)
        if (!Schema::hasTable('event_planning_budget_items')) {
            Schema::create('event_planning_budget_items', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('event_id')->constrained('event_planning_events')->onDelete('cascade');
                $table->unsignedBigInteger('tenant_id')->index();
                
                $table->string('category')->index();
                $table->string('title');
                $table->integer('estimated_kopecks')->unsigned()->default(0);
                $table->integer('actual_kopecks')->unsigned()->default(0);
                $table->string('status')->default('estimated')->index(); // estimated, partially_paid, paid
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Детализация бюджета праздника');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_planning_budget_items');
        Schema::dropIfExists('event_planning_vendors');
        Schema::dropIfExists('event_planning_events');
    }
};
