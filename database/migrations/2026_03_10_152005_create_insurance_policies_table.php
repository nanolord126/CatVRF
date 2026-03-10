<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('policyholder_id')->constrained('users');
            $table->string('policy_number')->unique();
            $table->enum('type', ['health', 'auto', 'home', 'life'])->default('health');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->decimal('premium_amount', 10, 2);
            $table->decimal('coverage_amount', 10, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_policies');
    }
};
