<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            if (Schema::hasTable('tenants')) {
                return;
            }
            $table->string('id')->primary();

            // Custom fields based on CANON 2026
            $table->uuid('uuid')->nullable()->unique()->index();
            $table->string('correlation_id')->nullable()->index();

            $table->string('name')->comment('Название организации');
            $table->string('type')->comment('Тип бизнеса, например, hotel или beauty');
            $table->string('slug')->unique()->comment('Уникальный идентификатор для URL');

            // Legal information
            $table->string('inn', 12)->nullable()->index()->comment('ИНН');
            $table->string('kpp', 9)->nullable()->comment('КПП');
            $table->string('ogrn', 15)->nullable()->comment('ОГРН');
            $table->string('legal_entity_type')->nullable()->comment('Тип юр. лица (ООО, ИП)');
            $table->text('legal_address')->nullable()->comment('Юридический адрес');
            $table->text('actual_address')->nullable()->comment('Фактический адрес');

            // Contact information
            $table->string('phone')->nullable()->comment('Контактный телефон');
            $table->string('email')->nullable()->comment('Контактный email');
            $table->string('website')->nullable()->comment('Веб-сайт');

            // Statuses
            $table->boolean('is_active')->default(true)->comment('Активен ли тенант');
            $table->boolean('is_verified')->default(false)->comment('Верифицирован ли тенант');

            // Metadata
            $table->json('meta')->nullable()->comment('Дополнительные метаданные (настройки, UI-предпочтения)');
            $table->jsonb('tags')->nullable()->comment('Теги для аналитики и фильтрации');

            $table->timestamps();

            // Obsolete fields from old migration - keep for compatibility if needed, but better to remove
            // $table->string('plan')->default('basic');
            // $table->dateTime('trial_ends_at')->nullable();
            // $table->boolean('is_paid')->default(false);
            // $table->json('data')->nullable();

            $table->comment('Таблица тенантов (организаций-клиентов)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};


