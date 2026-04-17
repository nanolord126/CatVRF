<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shift_schedules')) {
            return;
        }

        Schema::create('shift_schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиала)');
            $table->unsignedBigInteger('employee_id')->index();
            
            $table->string('shift_type')->default('regular')->comment('regular, night, overtime');
            $table->timestamp('start_time')->index();
            $table->timestamp('end_time')->index();
            $table->string('status')->default('scheduled')->index()->comment('scheduled, in_progress, completed, cancelled');
            
            $table->text('notes')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'start_time']);
            $table->index(['tenant_id', 'status', 'start_time']);
            $table->comment('Графики смен сотрудников');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_schedules');
    }
};
