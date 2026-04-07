<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для системы AI-конструкторов и анализа вкусов пользователей
 * CANON 2026: idempotent, correlation_id, comments
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. AI-дизайны и конструкции пользователей
        if (!Schema::hasTable('user_ai_designs')) {
            Schema::create('user_ai_designs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('vertical')->comment('beauty, furniture, food, auto, etc.');
                $table->json('design_data')->comment('Основные данные генерации');
                $table->json('suggestions')->nullable()->comment('Рекомендованные товары из Inventory');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Результаты работы AI-конструкторов пользователей');
            });
        }

        // 2. История просмотров товаров (для анализа вкусов)
        if (!Schema::hasTable('product_views')) {
            Schema::create('product_views', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
                $table->string('product_category')->index();
                $table->integer('duration_seconds')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->comment('История просмотров товаров для ML-анализа интересов');
            });
        }

        // 3. Обновление таблицы пользователей (профиль вкусов)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'taste_profile')) {
                $table->json('taste_profile')->nullable()->after('email')
                    ->comment('ML-профиль предпочтений: категории, цены, размеры, бренды');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_ai_designs');
        Schema::dropIfExists('product_views');
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'taste_profile')) {
                $table->dropColumn('taste_profile');
            }
        });
    }
};


