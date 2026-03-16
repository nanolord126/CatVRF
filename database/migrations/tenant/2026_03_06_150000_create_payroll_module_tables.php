<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft'); // draft, processed
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->string('correlation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('base_salary', 15, 2);
            $table->decimal('commissions', 15, 2)->default(0);
            $table->decimal('bonuses', 15, 2)->default(0);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2);
            $table->string('status')->default('pending'); // pending, paid
            $table->string('correlation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('reason');
            $table->date('date');
            $table->string('status')->default('pending'); // pending, applied
            $table->string('correlation_id')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_payroll_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_configs');
        Schema::dropIfExists('employee_deductions');
        Schema::dropIfExists('salary_slips');
        Schema::dropIfExists('payroll_runs');
    }
};
