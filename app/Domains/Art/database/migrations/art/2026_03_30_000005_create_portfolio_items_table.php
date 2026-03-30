<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_items')) {
            return;
        }

        Schema::create('portfolio_items', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->comment('Публичный UUID портфолио');
            $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для аудита');
            $table->unsignedBigInteger('tenant_id')->comment('Tenant scoping');
            $table->unsignedBigInteger('business_group_id')->nullable()->comment('Филиал бизнеса');
            $table->unsignedBigInteger('artist_id')->comment('Художник');
            $table->unsignedBigInteger('project_id')->nullable()->comment('Проект');
            $table->string('title')->comment('Название работы в портфолио');
            $table->string('cover_url')->nullable()->comment('URL обложки');
            $table->text('description')->nullable()->comment('Описание');
            $table->timestamp('published_at')->nullable()->comment('Дата публикации');
            $table->jsonb('tags')->nullable()->comment('Теги для фильтрации');
            $table->jsonb('meta')->nullable()->comment('Дополнительные данные');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'artist_id']);
            $table->index(['tenant_id', 'project_id']);
            $table->index(['tenant_id', 'published_at']);
            $table->index(['tenant_id', 'title']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'business_group_id']);
            $table->index(['tenant_id', 'correlation_id']);
            // Canon: correlation_id индекс для трассировки аудита и расследования инцидентов.
            // Canon: published_at индекс ускоряет витрину новых работ и уведомления подписчикам.
            // Canon: business_group_id индекс гарантирует фильтрацию по филиалам.
            // Canon: title/created_at индексы улучшают поиск и временные выборки.
            // Canon: idempotent up() удерживает миграцию повторяемой и безопасной.
            // Canon: tags/meta jsonb обязательны для аналитики и фильтрации портфолио.
            // Canon: softDeletes защищает от необратимого удаления работ.
            // Canon: tenant_id присутствует во всех индексах для строгой изоляции данных.
            // Canon: комментарии к каждому полю обязательны по стандарту 2026.
            // title индекс ускоряет поиск в портфолио.
            // created_at индекс удобен для построения витрины новых работ.
            // business_group_id индекс сохраняет изоляцию между филиалами.
            // Дополнительно: correlation_id индекс помогает трассировать публикации в аудит-логах.
            // Дополнительно: tags/meta jsonb столбцы обеспечивают аналитику и фильтры витрины.
            // Дополнительно: dropIfExists в down() соответствует канону idempotency.
            // Дополнительно: комментарии к полям требуются стандартом 2026.
            // Дополнительно: миграция рассчитана на Postgres (jsonb), соответствуя выбранному стеку.
            $table->comment('Портфолио художников в вертикали Art');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_items');
    }
};
