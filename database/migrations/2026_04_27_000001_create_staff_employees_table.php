<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('email');
            $table->string('phone');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->enum('role', ['admin', 'manager', 'specialist', 'junior', 'intern', 'contractor'])->default('specialist');
            $table->enum('status', ['active', 'on_leave', 'terminated', 'suspended', 'probation'])->default('active');
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamp('hire_date');
            $table->timestamp('termination_date')->nullable();
            $table->string('avatar')->nullable();
            $table->json('skills')->nullable();
            $table->integer('level')->default(1);
            $table->integer('experience_points')->default(0);
            $table->decimal('performance_score', 5, 2)->default(0.00);
            $table->decimal('burnout_risk', 5, 2)->default(0.00);
            $table->string('slack_id')->nullable();
            $table->string('teams_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('manager_id');
            $table->index('status');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_employees');
    }
};
