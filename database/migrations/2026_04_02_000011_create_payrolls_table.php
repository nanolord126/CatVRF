<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', static function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('base_salary_kopecks');
            $table->unsignedBigInteger('bonuses_kopecks')->default(0);
            $table->unsignedBigInteger('deductions_kopecks')->default(0);
            $table->unsignedBigInteger('total_kopecks')->storedAs(
                'base_salary_kopecks + bonuses_kopecks - deductions_kopecks'
            );
            $table->enum('status', ['draft', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'period_start']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
