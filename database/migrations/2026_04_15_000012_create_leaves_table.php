<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leaves')) {
            return;
        }

        Schema::create('leaves', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиала)');
            $table->unsignedBigInteger('employee_id')->index();
            
            $table->string('leave_type')->index()->comment('annual, sick, personal, maternity, paternity, unpaid');
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->unsignedInteger('days')->comment('Количество дней отпуска');
            $table->text('reason')->nullable();
            
            $table->string('status')->default('pending')->index()->comment('pending, approved, rejected, cancelled');
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'employee_id', 'status']);
            $table->index(['tenant_id', 'start_date', 'end_date']);
            $table->comment('Заявки на отпуска сотрудников');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
