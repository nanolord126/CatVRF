<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица search_queries — лог поисковых запросов пользователей.
 *
 * Используется SearchRankingService для ML-ранжирования,
 * аналитики (popular queries, zero results), персонализации поиска
 * через UserTasteProfile и A/B тестирования алгоритмов.
 * Данные анонимизированы (anonymized_user_id из AnonymizationService).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index();

            // Запрос (обезличенный — user_id не хранится, только anonymized_id)
            $table->string('anonymized_user_id', 64)->nullable()->index()
                ->comment('SHA-256(user_id + salt), GDPR-compliant');
            $table->string('query')->index()->comment('Сам поисковый запрос');
            $table->string('vertical')->nullable()->index()->comment('beauty, food, furniture...');

            // Контекст запроса
            $table->json('filters')->nullable()->comment('Активные фильтры: price_min, price_max, brand и т.д.');
            $table->string('sort_by')->nullable()->comment('relevance, price_asc, rating...');

            // Результат
            $table->integer('results_count')->default(0)->comment('Сколько результатов найдено');
            $table->boolean('zero_results')->default(false)->index()->comment('Запрос без результатов (для аналитики)');
            $table->integer('clicked_position')->nullable()->comment('Позиция кликнутого результата');
            $table->unsignedBigInteger('clicked_entity_id')->nullable()->comment('ID товара/услуги по которому кликнули');
            $table->string('clicked_entity_type')->nullable()->comment('Product, Service, Master...');

            // Источник и устройство
            $table->enum('source', ['web', 'mobile', 'api', 'b2b_api'])->default('web');
            $table->string('device_type')->nullable()->comment('mobile, tablet, desktop');

            // ML-ранжирование
            $table->string('ranking_algo_version')->nullable()->comment('Версия алгоритма ранжирования');
            $table->float('personalization_score')->nullable()->comment('0.0-1.0 — насколько персонализирована выдача');

            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'query']);
            $table->index(['tenant_id', 'vertical', 'created_at']);
            $table->index(['zero_results', 'vertical']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
