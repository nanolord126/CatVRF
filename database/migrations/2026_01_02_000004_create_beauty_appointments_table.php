<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('beauty_appointments')) {
            return;
        }

        Schema::create('beauty_appointments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('salon_id')
                ->constrained('beauty_salons')
                ->cascadeOnDelete()
                ->comment('Салон, в котором совершается запись');
            $table->foreignId('master_id')
                ->constrained('beauty_masters')
                ->restrictOnDelete()
                ->comment('Мастер, к которому записан клиент');
            $table->foreignId('service_id')
                ->constrained('beauty_services')
                ->restrictOnDelete()
                ->comment('Выбранная услуга');
            $table->unsignedBigInteger('client_id')->index()->comment('ID пользователя-клиента из таблицы users');
            $table->dateTime('start_at')->comment('Дата и время начала визита');
            $table->dateTime('end_at')->nullable()->comment('Дата и время окончания (start_at + duration)');
            $table->unsignedInteger('price_cents')->comment('Итоговая цена в копейках');
            $table->string('status', 30)->default('pending')->index()->comment('pending|confirmed|completed|cancelled|no_show');
            $table->text('cancellation_reason')->nullable()->comment('Причина отмены (заполняется при status=cancelled)');
            $table->string('correlation_id', 36)->index()->comment('ID для сквозного трейсинга');
            $table->json('tags')->nullable()->comment('Теги для аналитики');
            $table->timestamps();

            $table->index(['master_id', 'start_at', 'status']);
            $table->index(['salon_id', 'start_at']);
            $table->index(['client_id', 'start_at']);
            $table->index(['salon_id', 'status']);
        });

        // SQLite doesn't support COMMENT ON TABLE
        if (config('database.default') !== 'sqlite') {
            \Illuminate\Support\Facades\DB::statement(
                "COMMENT ON TABLE beauty_appointments IS 'Записи клиентов в салоны красоты (вертикаль Beauty)'"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('beauty_appointments');
    }
};
