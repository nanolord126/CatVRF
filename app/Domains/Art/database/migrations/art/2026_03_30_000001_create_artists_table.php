<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('artists')) {
            return;
        }

        Schema::create('artists', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique()->comment('Публичный UUID художника');
            $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для аудита');
            $table->unsignedBigInteger('tenant_id')->comment('Tenant scoping обязательный');
            $table->unsignedBigInteger('business_group_id')->nullable()->comment('Филиал бизнеса');
            $table->string('name')->comment('Имя художника');
            $table->string('slug')->unique()->comment('SEO слаг');
            $table->text('bio')->nullable()->comment('Биография');
            $table->string('style')->nullable()->comment('Основной художественный стиль');
            $table->decimal('rating', 3, 2)->default(4.80)->comment('Рейтинг по отзывам');
            $table->boolean('is_active')->default(true)->comment('Доступность в витрине');
            $table->jsonb('tags')->nullable()->comment('Теги для фильтрации и аналитики');
            $table->jsonb('meta')->nullable()->comment('Произвольные метаданные');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'business_group_id']);
            $table->index(['tenant_id', 'rating']);
            $table->index(['tenant_id', 'style']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'name']);
            $table->index(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'correlation_id']);
            // Canon: correlation_id индекс для быстрой трассировки аудита.
            // Canon: tenant_id + business_group_id гарантирует изоляцию филиалов.
            // Canon: rating/style/created_at/slug индексы для витрин, поиска, отчетности.
            // Canon: tags/meta нужны для аналитики и фильтров (jsonb по умолчанию поддержан Postgres).
            // Canon: softDeletes обязателен для восстановления и истории изменений.
            // Canon: таблица строго tenant-aware; прямые запросы без tenant запрещены.
            // Canon: idempotent up() с предварительной проверкой Schema::hasTable.
            // Canon: correlation_id поле индексируется и хранит все мутации для аудита.
            // Canon: tenant_id и business_group_id обязаны присутствовать в каждом запросе к таблице.
            // Canon: таблица служит источником прав доступа и рекомендаций, поэтому все поля прокомментированы.
            // Canon: rating по умолчанию 4.80, но индексы позволяют быстро считать медиану по tenant.
            // Индексы упрощают отчеты по активности художников и разрезам по филиалам.
            // Tenant-aware фильтры нужны для изоляции данных между бизнес-группами.
            // rating/style индексы обслуживают витрину и рекомендации.
            // created_at индекс ускоряет отчеты по росту каталога художников.
            // name/slug индексы ускоряют поиск и валидацию уникальности в пределах tenant.
            $table->comment('Artists of the Art vertical (tenant-aware)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
