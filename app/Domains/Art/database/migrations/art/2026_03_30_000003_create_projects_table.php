<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            return;
        }

        Schema::create('projects', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->comment('Публичный UUID проекта');
            $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
            $table->unsignedBigInteger('tenant_id')->comment('Tenant scoping обязательный');
            $table->unsignedBigInteger('business_group_id')->nullable()->comment('Филиал бизнеса');
            $table->unsignedBigInteger('artist_id')->comment('Ответственный художник');
            $table->string('title')->comment('Название проекта');
            $table->text('brief')->nullable()->comment('Описание и бриф');
            $table->unsignedBigInteger('budget_cents')->default(0)->comment('Бюджет проекта в копейках');
            $table->string('status')->default('draft')->comment('Статус проекта');
            $table->string('mode')->default('b2c')->comment('B2C или B2B');
            $table->timestamp('deadline_at')->nullable()->comment('Дедлайн');
            $table->jsonb('preferences')->nullable()->comment('Клиентские предпочтения');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики');
            $table->jsonb('meta')->nullable()->comment('Расширенные данные');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'mode']);
            $table->index(['tenant_id', 'artist_id']);
            $table->index(['tenant_id', 'deadline_at']);
            $table->index(['tenant_id', 'budget_cents']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'business_group_id']);
            $table->index(['tenant_id', 'correlation_id']);
            // Canon: correlation_id индекс для быстрого сопоставления с журналами аудита.
            // Canon: mode/status индексы нужны для SLA, rate limiting и витрины B2C/B2B.
            // Canon: deadline_at/budget_cents индексы поддерживают мониторинг просрочек и бюджета.
            // Canon: business_group_id индекс усиливает филиальную изоляцию по канону.
            // Canon: created_at индекс помогает в прогнозах спроса и аналитике загрузки.
            // Canon: tags/meta jsonb обязательны для аналитики и ML признаков.
            // Canon: idempotent проверка Schema::hasTable гарантирует отсутствие дублирования.
            // Canon: tenant_id обязателен в каждой выборке; индексы сохраняют производительность.
            // Canon: softDeletes позволяет хранить историю проектов без физического удаления.
            // Canon: комментарии к полям фиксируют бизнес-назначение для аудита БД.
            // deadline_at индекс важен для SLA мониторинга и выборки просроченных проектов.
            // budget_cents индекс ускоряет отчеты по бюджету внутри tenant.
            // created_at индекс обслуживает временные графики и прогнозы спроса.
            // business_group_id индекс нужен для филиальной изоляции и фильтрации.
            $table->comment('Проекты вертикали Art с tenant scoping');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
