<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reviews')) {
            return;
        }

        Schema::create('reviews', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->comment('Публичный UUID отзыва');
            $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
            $table->unsignedBigInteger('tenant_id')->comment('Tenant scoping обязательный');
            $table->unsignedBigInteger('business_group_id')->nullable()->comment('Филиал бизнеса');
            $table->unsignedBigInteger('project_id')->comment('Связанный проект');
            $table->unsignedBigInteger('artist_id')->comment('Художник');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Пользователь, оставивший отзыв');
            $table->unsignedTinyInteger('rating')->default(5)->comment('Оценка 1-5');
            $table->text('comment')->nullable()->comment('Текст отзыва');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики');
            $table->jsonb('meta')->nullable()->comment('Дополнительные данные');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'project_id']);
            $table->index(['tenant_id', 'rating']);
            $table->index(['tenant_id', 'artist_id']);
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'correlation_id']);
            // Canon: correlation_id индекс ускоряет поиск событий в аудит-логах.
            // Canon: tenant_id + project_id обязательны для корректной агрегации отзывов по проекту.
            // Canon: rating индекс ускоряет метрики качества и выборку отзывов для ML.
            // Canon: created_at индекс для cron-отчетов и уведомлений.
            // Canon: artist_id/user_id индексы поддерживают фильтры по автору и художнику.
            // Canon: idempotent up() с предварительной проверкой Schema::hasTable.
            // Canon: tags/meta jsonb обязательны для аналитики качества и тональности.
            // Canon: softDeletes сохраняет историю отзывов и позволяет откатывать ошибочные удаления.
            // Canon: tenant_id присутствует в каждом индексе для жесткой мультитенантности.
            // Canon: комментарии на столбцах обязательны для эксплуатационной документации.
            // user_id индекс удобен для выгрузки отзывов конкретного пользователя.
            // artist_id индекс ускоряет сводки рейтингов по художникам.
            // created_at индекс помогает в анализе динамики отзывов и алертинге.
            // Дополнительно: correlation_id индекс связывает отзыв с событиями FraudControlService.
            // Дополнительно: комментарии обязательны для соответствия канону 2026 и аудиту.
            // Дополнительно: down() использует dropIfExists без побочных эффектов.
            // Дополнительно: tags/meta jsonb поля позволяют работать с анализом тональности.
            // Дополнительно: миграция рассчитана на Postgres с jsonb, что следует канону.
            // Дополнительно: все поля снабжены комментариями для Data Catalog.
            $table->comment('Отзывы по арт-проектам');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
