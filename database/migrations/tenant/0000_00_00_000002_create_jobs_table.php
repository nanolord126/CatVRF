<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Создает таблицы для управления очередью заданий с поддержкой
     * трассировки (correlation_id), батчей и отказов.
     * Production 2026: uuid, tags (jsonb), составные индексы, комментарии.
     */
    public function up(): void
    {
        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->comment('Очередь асинхронных заданий (Laravel Queue + Horizon).');

                $table->id();
                $table->string('queue')->index()->comment('Название очереди');
                $table->longText('payload')->comment('Сериализованная задача');
                $table->unsignedTinyInteger('attempts')->default(0)->comment('Попытки выполнения');
                $table->unsignedInteger('reserved_at')->nullable()->index()->comment('Время резерва');
                $table->unsignedInteger('available_at')->index()->comment('Время доступности');
                $table->unsignedInteger('created_at')->index()->comment('Время создания');

                // Traceability & Production 2026
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID для трассировки');
                $table->uuid('uuid')->nullable()->unique()->index()->comment('Уникальный ID задачи');
                $table->jsonb('tags')->nullable()->comment('Теги для фильтрации и аналитики');

                // Составной индекс для быстрой фильтрации по очереди
                $table->index(['queue', 'reserved_at'], 'idx_jobs_queue_reserved');
            });
        }

        if (!Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->comment('Батчи задач для Horizon (группировка связанных заданий).');

                $table->string('id')->primary()->comment('Уникальный ID батча');
                $table->string('name')->comment('Название батча');
                $table->integer('total_jobs')->comment('Всего задач');
                $table->integer('pending_jobs')->comment('Ожидающие');
                $table->integer('failed_jobs')->comment('Неудачные');
                $table->longText('failed_job_ids')->comment('ID неудачных задач');
                $table->mediumText('options')->nullable()->comment('Опции батча');
                $table->integer('cancelled_at')->nullable()->comment('Время отмены');
                $table->integer('created_at')->comment('Время создания');
                $table->integer('finished_at')->nullable()->comment('Время завершения');

                // Traceability & Production 2026
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
                $table->jsonb('tags')->nullable()->comment('Теги батча');
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->comment('Неудачные задания для анализа и повторной обработки.');

                $table->id();
                $table->string('uuid')->unique()->index()->comment('Уникальный ID неудачной задачи');
                $table->text('connection')->comment('Соединение (database, redis)');
                $table->text('queue')->comment('Очередь');
                $table->longText('payload')->comment('Payload задачи');
                $table->longText('exception')->comment('Стек ошибки');
                $table->timestamp('failed_at')->useCurrent()->index()->comment('Время провала');

                // Traceability & Production 2026
                $table->string('correlation_id')->nullable()->index()->comment('Correlation ID');
                $table->jsonb('tags')->nullable()->comment('Теги ошибки');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('failed_jobs');
    }
};