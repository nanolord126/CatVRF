<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для графиков мастеров красоты.
 * Канон 2026: idempotent, comments, tenant_id, correlation_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('master_schedules')) {
            Schema::create('master_schedules', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->index();
                $table->foreignId('master_id')->constrained('masters')->onDelete('cascade');
                $table->date('date')->index();
                $table->jsonb('slots')->comment('Рабочие интервалы (например, [{"start": "09:00", "end": "18:00"}])');
                $table->jsonb('blocked_hours')->nullable()->comment('Заблокированные часы (обед, перерыв)');
                $table->boolean('is_day_off')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->unique(['master_id', 'date']);
                $table->comment('Таблица расписания и рабочих смен мастеров');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_schedules');
    }
};


