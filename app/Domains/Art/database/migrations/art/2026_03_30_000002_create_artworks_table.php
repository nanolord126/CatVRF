<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('artworks')) {
            return;
        }

        Schema::create('artworks', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->comment('Публичный UUID работы');
            $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для аудита');
            $table->unsignedBigInteger('tenant_id')->comment('Tenant scoping');
            $table->unsignedBigInteger('business_group_id')->nullable()->comment('Филиал бизнеса');
            $table->unsignedBigInteger('artist_id')->comment('Связанный художник');
            $table->unsignedBigInteger('project_id')->nullable()->comment('Проект, к которому относится работа');
            $table->string('title')->comment('Название работы');
            $table->text('description')->nullable()->comment('Описание работы');
            $table->unsignedBigInteger('price_cents')->default(0)->comment('Цена в копейках');
            $table->boolean('is_visible')->default(true)->comment('Отображение в витрине');
            $table->timestamp('delivered_at')->nullable()->comment('Дата сдачи работы');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики');
            $table->jsonb('meta')->nullable()->comment('Мета-данные для кастомных полей');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'artist_id']);
            $table->index(['tenant_id', 'project_id']);
            $table->index(['tenant_id', 'is_visible']);
            $table->index(['tenant_id', 'delivered_at']);
            $table->index(['tenant_id', 'title']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'price_cents']);
            $table->index(['tenant_id', 'correlation_id']);
            // Canon: correlation_id индекс нужен для аудита и расследований.
            // Canon: tenant_id + project_id связка обеспечивает корректную выборку работ в проекте.
            // Canon: delivered_at и price_cents ускоряют отчеты по SLA и выручке.
            // Canon: created_at поддерживает временные графики и тренды витрины.
            // Canon: title/artist индексы ускоряют поиск и ленты рекомендаций.
            // Canon: idempotent up() гарантирует отсутствие повторного создания таблицы.
            // Canon: tags/meta jsonb столбцы обязательны для аналитики и фильтрации.
            // Canon: tenant_id в каждом индексе подтверждает строгую мультитенантность.
            // Canon: softDeletes сохраняет историю работ для восстановления и аудита.
            // Canon: комментарии к полям упрощают аудит и документацию БД.
            // price_cents индекс ускоряет сортировку по стоимости в витрине.
            // delivered_at индекс помогает строить отчеты по срокам сдачи работ.
            // title индекс улучшает поиск по каталогу в рамках tenant.
            // created_at индекс нужен для дашбордов активности художников.
            // Дополнительно: индекс tenant_id + correlation_id помогает FraudMLService привязать скор к записи.
            // Дополнительно: комментарии к таблице и полям необходимы по стандарту аудита 2026.
            // Дополнительно: idempotent down() использует dropIfExists без побочных эффектов.
            $table->comment('Artworks привязаны к проектам и художникам');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artworks');
    }
};
