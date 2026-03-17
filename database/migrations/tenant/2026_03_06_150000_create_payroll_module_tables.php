<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('payroll')) {
            Schema::create('payroll', function (Blueprint $table) {
                $table->comment('Зарплата: ведомости, расчеты, налоги.');
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->decimal('base_salary', 15, 2);
                $table->decimal('total', 15, 2);
                $table->date('period_start');
                $table->date('period_end');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('payroll'); }
};
