<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('time_entries')) {
            return;
        }

        Schema::create('time_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиала)');
            $table->unsignedBigInteger('employee_id')->index();
            $table->unsignedBigInteger('shift_id')->nullable()->index();
            
            $table->timestamp('clock_in')->index();
            $table->timestamp('clock_out')->nullable()->index();
            $table->unsignedInteger('duration_minutes')->default(0)->comment('Длительность в минутах');
            $table->string('status')->default('active')->index()->comment('active, completed');
            
            $table->text('notes')->nullable();
            $table->decimal('gps_latitude', 10, 7)->nullable()->comment('GPS при clock in');
            $table->decimal('gps_longitude', 11, 7)->nullable()->comment('GPS при clock in');
            
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'clock_in']);
            $table->index(['tenant_id', 'status', 'clock_in']);
            $table->comment('Учёт рабочего времени сотрудников');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
