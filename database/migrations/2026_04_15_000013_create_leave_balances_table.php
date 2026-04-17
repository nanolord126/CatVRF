<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leave_balances')) {
            return;
        }

        Schema::create('leave_balances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index()->comment('ID бизнес-группы (филиала)');
            $table->unsignedBigInteger('employee_id')->index()->unique();
            
            $table->string('leave_type')->comment('annual, sick, personal');
            $table->unsignedInteger('balance')->default(0)->comment('Остаток дней отпуска');
            $table->unsignedInteger('accrued')->default(0)->comment('Начислено за год');
            $table->unsignedInteger('used')->default(0)->comment('Использовано за год');
            
            $table->unsignedInteger('year')->default((int) date('Y'))->index();
            
            $table->string('correlation_id', 36)->nullable()->index();
            $table->json('tags')->nullable()->comment('Теги для аналитики и фильтрации');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'employee_id', 'leave_type', 'year']);
            $table->comment('Балансы отпусков сотрудников');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
