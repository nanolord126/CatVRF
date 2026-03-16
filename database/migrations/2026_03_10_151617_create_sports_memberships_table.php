<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sports_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('athlete_id')->constrained('users');
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->enum('status', ['active', 'suspended', 'expired'])->default('active');
            $table->timestamp('expires_at');
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('status');

            $table->string('correlation_id')->nullable()->index();        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sports_memberships');
    }
};

