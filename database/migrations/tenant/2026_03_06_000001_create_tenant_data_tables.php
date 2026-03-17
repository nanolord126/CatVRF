<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает основные таблицы данных тенанта: настройки, конфигурация, метаданные.
     * Production 2026: idempotent, correlation_id, tags, документация.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tenant_settings')) {
            Schema::create('tenant_settings', function (Blueprint $table) {
                $table->comment('Настройки и конфигурация тенанта.');
                
                $table->id();
                $table->string('key')->unique()->comment('Ключ настройки');
                $table->jsonb('value')->comment('Значение (JSON)');
                $table->string('description')->nullable()->comment('Описание');
                $table->timestamps();
                
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
                $table->jsonb('tags')->nullable()->comment('Теги');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
