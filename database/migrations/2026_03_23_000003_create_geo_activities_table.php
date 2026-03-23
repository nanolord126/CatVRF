<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateGeoActivitiesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('geo_activities')) {
            return;
        }

        Schema::create('geo_activities', static function (Blueprint $table): void {
            $table->id()->comment('Первичный ключ');
            $table->uuid()->unique()->indexed()->comment('UUID события');
            $table->unsignedBigInteger('tenant_id')->nullable()->indexed()->comment('ID тенанта для изоляции');
            $table->unsignedBigInteger('user_id')->nullable()->indexed()->comment('ID пользователя (анонимизирован в публичной статистике)');
            $table->enum('activity_type', ['view', 'purchase', 'booking', 'click', 'search', 'review'])->indexed()->comment('Тип активности');
            $table->string('vertical', 100)->nullable()->indexed()->comment('Вертикаль (auto, beauty, food, hotels и т.д.)');
            $table->decimal('latitude', 10, 8)->nullable()->comment('Широта (анонимизирована)');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Долгота (анонимизирована)');
            $table->string('city', 100)->nullable()->comment('Город (нормализованный)');
            $table->string('region', 100)->nullable()->comment('Регион');
            $table->string('country', 2)->default('RU')->comment('Код страны');
            $table->json('metadata')->nullable()->comment('Доп. данные: product_id, service_id, price и т.д.');
            $table->string('correlation_id', 36)->nullable()->indexed()->comment('ID для трейсинга');
            $table->timestamp('recorded_at')->useCurrent()->comment('Время события (UTC)');

            $table->index(['tenant_id', 'activity_type', 'recorded_at'], 'idx_geo_activities_composite');
            $table->index(['latitude', 'longitude'], 'idx_geo_activities_coordinates');

            $table->comment('Таблица активности пользователей для гео-тепловых карт');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('geo_activities');
    }
}
