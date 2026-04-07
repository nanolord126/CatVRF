<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('full_name');
            $table->string('position');               // courier, master, manager, admin, etc.
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'freelance']);
            $table->unsignedBigInteger('base_salary_kopecks')->default(0); // в копейках
            $table->json('additional_payments')->nullable();   // KPI, надбавки
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
