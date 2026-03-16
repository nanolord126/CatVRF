<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Добавляем HR-данные пользователям (сотрудникам)
        Schema::table('users', function (Blueprint $table) {
            $table->string('role_code')->nullable()->after('email'); // MASTER, HOUSEKEEPER, ADMIN, etc.
            $table->string('phone')->nullable()->after('role_code');
            $table->text('address')->nullable()->after('phone');
            $table->json('geo_location')->nullable()->after('address'); // {lat: ..., lng: ...}
            $table->date('hired_at')->nullable()->after('geo_location');
            $table->date('fired_at')->nullable()->after('hired_at');
        });

        // Посещаемость и учет времени
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->json('clock_in_geo')->nullable();
            $table->json('clock_out_geo')->nullable();
            $table->string('status')->default('present'); // present, late, absence
            $table->decimal('total_hours', 5, 2)->default(0);
            $table->string('correlation_id')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        // Запросы на отпуск / больничный
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // vacation, sick, unpaid
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('correlation_id')->nullable();
            $table->timestamps();
        });

        // Смены (расширяем staff_schedules если нужно, но по канону лучше создать или связать)
        // В существующей staff_schedules уже есть user_id, date, start_time, end_time.
        // Добавим поле для типа смены или статуса в staff_schedules через миграцию
        Schema::table('staff_schedules', function (Blueprint $table) {
            $table->string('shift_type')->default('regular')->after('end_time'); // day, night, flexible
            $table->string('status')->default('scheduled')->after('shift_type'); // scheduled, completed, cancelled
        });
    }

    public function down(): void
    {
        Schema::table('staff_schedules', function (Blueprint $table) {
            $table->dropColumn(['shift_type', 'status']);
        });
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role_code', 'phone', 'address', 'geo_location', 'hired_at', 'fired_at']);
        });
    }
};
