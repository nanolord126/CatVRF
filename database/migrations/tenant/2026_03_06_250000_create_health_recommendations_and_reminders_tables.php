<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Таблица персональных рекомендаций и чеклистов здоровья/ухода
        Schema::create('user_health_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('target_type', ['HUMAN', 'ANIMAL'])->default('HUMAN');
            $table->unsignedBigInteger('target_id'); // User ID or Animal ID
            $table->string('title'); // Например: "Прием витаминов", "Прививка от бешенства"
            $table->text('description')->nullable();
            $table->enum('frequency', ['ONCE', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'])->default('ONCE');
            $table->date('next_due_date')->index();
            $table->boolean('is_completed')->default(false);
            $table->json('history_log')->nullable(); // Даты завершения прошлых циклов
            $table->foreignId('medical_card_id')->nullable()->constrained('medical_cards'); // Основание рекомендации
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // Таблица напоминаний о визитах (Appointments Notifications)
        Schema::create('user_visit_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('appointment_id'); // Ссылка на визит
            $table->dateTime('remind_at'); // Время когда нужно отправить пуш/смс
            $table->boolean('sent_at')->nullable();
            $table->enum('channel', ['PUSH', 'SMS', 'EMAIL'])->default('PUSH');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_visit_reminders');
        Schema::dropIfExists('user_health_recommendations');
    }
};
