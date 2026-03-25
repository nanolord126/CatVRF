<?php

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
        if (Schema::hasTable('appointments')) {
            return;
        }

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_group_id')->nullable()->constrained()->nullOnDelete();
            
            // Полиморфная привязка (может быть мастер, врач, мойщик, столик в ресторане)
            $table->nullableMorphs('bookable');
            
            // Клиент
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            
            // Данные времени
            $table->dateTime('datetime_start')->index();
            $table->dateTime('datetime_end')->index();
            
            // Финансы и статус
            $table->integer('price_cents')->default(0)->comment('Сумма в копейках');
            $table->string('status', 50)->default('pending')->index()->comment('pending, confirmed, completed, cancelled');
            $table->string('payment_status', 50)->default('unpaid')->index();
            
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->comment('Универсальная таблица записей (Appointments) для всех вертикалей');
        });

        // Таблица слотов занятости для ускорения поиска (Heatmap)
        Schema::create('availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('bookable_type', 255);
            $table->unsignedBigInteger('bookable_id');
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->index();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            $table->comment('Слоты доступности для визуализации в календарях');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_slots');
        Schema::dropIfExists('appointments');
    }
};
