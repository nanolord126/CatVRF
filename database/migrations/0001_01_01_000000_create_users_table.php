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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->comment('Первичный ключ');
            $table->uuid('uuid')->unique()->index()->comment('Публичный UUID пользователя');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete()->comment('ID тенанта (мультитенантность)');
            $table->string('name')->comment('Полное имя пользователя');
            $table->string('email')->unique()->comment('Email (уникален для каждого пользователя)');
            $table->timestamp('email_verified_at')->nullable()->comment('Когда email был верифицирован');
            $table->string('phone', 20)->nullable()->unique()->comment('Номер телефона (для SMS-2FA)');
            $table->timestamp('phone_verified_at')->nullable()->comment('Когда телефон был верифицирован');
            $table->string('password')->comment('Хеш пароля (bcrypt)');
            $table->string('correlation_id', 36)->nullable()->index()->comment('Correlation ID для трейсинга');
            
            // 2FA (двухфакторная аутентификация)
            $table->text('two_factor_secret')->nullable()->comment('Secret для TOTP (Authenticator)');
            $table->text('two_factor_recovery_codes')->nullable()->comment('Recovery codes для 2FA (JSON массив)');
            $table->timestamp('two_factor_confirmed_at')->nullable()->comment('Когда 2FA был подтвержден');
            
            // Метаданные и расширяемость
            $table->json('meta')->nullable()->comment('Дополнительные метаданные (расширяемо)');
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            
            // Логирование активности
            $table->timestamp('last_login_at')->nullable()->comment('Когда последний раз был вход');
            $table->timestamp('last_activity_at')->nullable()->comment('Последняя активность');
            
            // Управление учетной записью
            $table->boolean('is_active')->default(true)->index()->comment('Активен ли пользователь');
            $table->boolean('is_admin')->default(false)->index()->comment('Администратор платформы');
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('Email для восстановления пароля');
            $table->string('token')->comment('Уникальный токен восстановления');
            $table->timestamp('created_at')->nullable()->comment('Когда был создан токен');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Session ID');
            $table->foreignId('user_id')->nullable()->index()->constrained('users')->cascadeOnDelete()->comment('ID пользователя');
            $table->string('ip_address', 45)->nullable()->comment('IP адрес (IPv4/IPv6)');
            $table->text('user_agent')->nullable()->comment('User Agent браузера');
            $table->longText('payload')->comment('Данные сессии (сериализованно)');
            $table->integer('last_activity')->index()->comment('Timestamp последней активности');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};


