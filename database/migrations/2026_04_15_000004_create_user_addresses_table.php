<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица user_addresses — сохранённые адреса пользователя (до 5 штук).
 *
 * Используется UserAddressService для хранения адресов доставки,
 * самовывоза, домашнего и рабочего адресов.
 * Интеграция с Yandex Maps / Dadata для нормализации.
 * B2B: адреса привязаны к business_group_id (юридический адрес, склад).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index()
                ->comment('Для B2B — адрес привязан к филиалу');

            // Тип адреса
            $table->enum('type', ['home', 'work', 'delivery', 'pickup', 'legal', 'warehouse', 'other'])
                ->default('delivery')->index();

            // Полный адрес (нормализованный Dadata/Yandex)
            $table->string('raw_address')->comment('Строка как ввёл пользователь');
            $table->string('normalized_address')->nullable()->comment('Нормализованный через Dadata/Yandex');

            // Разбивка по полям
            $table->string('country', 2)->default('RU');
            $table->string('region')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('district')->nullable();
            $table->string('street')->nullable();
            $table->string('building')->nullable();
            $table->string('apartment')->nullable();
            $table->string('floor')->nullable();
            $table->string('entrance')->nullable();
            $table->string('intercom')->nullable();
            $table->string('postal_code', 10)->nullable();

            // Геокоординаты (из Maps API)
            $table->decimal('lat', 10, 8)->nullable()->index();
            $table->decimal('lon', 11, 8)->nullable()->index();

            // Метаданные
            $table->string('label')->nullable()->comment('Пользовательское название: "Дача", "Офис"');
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_verified')->default(false)->comment('Адрес подтверждён доставкой');
            $table->timestamp('last_used_at')->nullable();
            $table->json('delivery_instructions')->nullable()->comment('Доп. инструкции курьеру');
            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_default']);
            $table->index(['tenant_id', 'city']);
            $table->index(['lat', 'lon']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
