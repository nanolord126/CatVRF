<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Графики смен
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            
            $table->string('name')->default('Default Schedule');
            $table->string('type')->default('weekly'); // weekly, custom, rotating
            
            // Расписание
            $table->json('schedule')->nullable(); // JSON с расписанием по дням
            
            // Временной период
            $table->date('valid_from')->useCurrent();
            $table->date('valid_to')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'staff_id']);
            $table->index(['tenant_id', 'is_active']);
        });

        // Отпуска и отсутствия
        Schema::create('staff_timeoffs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('staff')->onDelete('set null');
            
            // Тип отсутствия
            $table->string('type'); // vacation, sick_leave, personal, bereavement, maternity, paternity
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            
            // Даты
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days')->default(1);
            
            // Детали
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            
            // Подтверждение
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Документы
            $table->string('attachment_url')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'staff_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'start_date']);
        });

        // Подмены сотрудников
        Schema::create('staff_shift_swaps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('original_staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('replacement_staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('shift_id')->constrained('staff_shifts')->onDelete('cascade');
            
            // Статус
            $table->string('status')->default('pending'); // pending, approved, rejected, completed
            
            // Причина
            $table->text('reason')->nullable();
            
            // Подтверждение
            $table->foreignId('approved_by')->nullable()->constrained('staff')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'original_staff_id']);
            $table->index(['tenant_id', 'replacement_staff_id']);
            $table->index(['tenant_id', 'status']);
        });

        // Учёт рабочего времени
        Schema::create('staff_time_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->foreignId('shift_id')->nullable()->constrained('staff_shifts')->onDelete('set null');
            
            // Время
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_minutes')->default(0);
            
            // Тип записи
            $table->string('entry_type')->default('work'); // work, break, overtime
            
            // Проект/задача
            $table->string('project_code')->nullable();
            $table->text('description')->nullable();
            
            // Геолокация
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            
            $table->timestamps();
            
            $table->index(['tenant_id', 'staff_id']);
            $table->index(['tenant_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_time_entries');
        Schema::dropIfExists('staff_shift_swaps');
        Schema::dropIfExists('staff_timeoffs');
        Schema::dropIfExists('staff_schedules');
    }
};
