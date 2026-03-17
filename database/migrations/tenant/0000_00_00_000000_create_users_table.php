<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает основные таблицы: users (аутентификация),
     * password_reset_tokens (сброс пароля), sessions (сессии).
     * Production 2026: idempotent, correlation_id, tags, документация.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->comment('Пользователи системы с аутентификацией.');

                $table->id();
                $table->string('name')->comment('Имя пользователя');
                $table->string('email')->unique()->comment('Email (уникальный)');
                $table->timestamp('email_verified_at')->nullable()->comment('Время подтверждения email');
                $table->string('password')->comment('Хеш пароля');
                $table->boolean('is_active')->default(true)->comment('Активность пользователя');
                $table->string('role')->default('client')->index()->comment('Роль пользователя');
                $table->rememberToken();
                $table->timestamps();

                // Traceability & Production 2026
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для трассировки');
                $table->jsonb('tags')->nullable()->comment('Теги для категоризации пользователей');
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->comment('Токены для сброса пароля.');

                $table->string('email')->primary()->comment('Email пользователя');
                $table->string('token')->comment('Токен сброса пароля');
                $table->timestamp('created_at')->nullable()->comment('Время создания токена');
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->comment('Сессии пользователей.');

                $table->string('id')->primary()->comment('Session ID');
                $table->foreignId('user_id')->nullable()->index()->comment('Ссылка на пользователя');
                $table->string('ip_address', 45)->nullable()->comment('IP адрес клиента');
                $table->text('user_agent')->nullable()->comment('User-Agent браузера');
                $table->longText('payload')->comment('Сериализованные данные сессии');
                $table->integer('last_activity')->index()->comment('Время последней активности');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

