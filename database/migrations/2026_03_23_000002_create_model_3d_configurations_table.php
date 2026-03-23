<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateModel3dConfigurationsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_3d_configurations')) {
            return;
        }

        Schema::create('model_3d_configurations', static function (Blueprint $table): void {
            $table->id()->comment('Первичный ключ');
            $table->uuid()->unique()->indexed()->comment('UUID для публичного доступа');
            $table->unsignedBigInteger('tenant_id')->indexed()->comment('ID тенанта');
            $table->unsignedBigInteger('model_3d_id')->indexed()->comment('ФoreignKey на models_3d');
            $table->string('name', 255)->comment('Название варианта (Красный, Синий и т.д.)');
            $table->json('config')->comment('JSONB конфигурация: {color, material, size, texture}');
            $table->decimal('price_modifier', 10, 2)->default(0)->comment('Модификатор цены в копейках');
            $table->enum('status', ['active', 'archived'])->default('active')->indexed()->comment('Статус конфигурации');
            $table->integer('usage_count')->default(0)->comment('Количество использований');
            $table->string('correlation_id', 36)->nullable()->indexed()->comment('ID для трейсинга');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('model_3d_id')->references('id')->on('models_3d')->cascadeOnDelete();
            $table->index(['tenant_id', 'status'], 'idx_3d_config_tenant_status');

            $table->comment('Варианты конфигурации 3D моделей (цвет, материал, размер)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_3d_configurations');
    }
}
