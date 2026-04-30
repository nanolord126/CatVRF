<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('loyalty_program_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('name')->comment('Bronze, Silver, Gold, Platinum, etc.');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            // Tier requirements
            $table->decimal('min_points', 10, 2)->default(0)->comment('Minimum points to reach this tier');
            $table->decimal('min_spend', 10, 2)->nullable()->comment('Minimum total spend to reach this tier');
            $table->integer('min_visits')->nullable()->comment('Minimum visits to reach this tier');
            
            // Tier benefits
            $table->decimal('point_multiplier', 5, 4)->default(1.0000)
                ->comment('Point earning multiplier (e.g., 1.5x = 50% more points)');
            $table->decimal('discount_percentage', 5, 4)->default(0)
                ->comment('Discount percentage (e.g., 0.1000 = 10%)');
            
            // Privileges (JSON for flexibility)
            $table->json('privileges')->nullable()->comment('Array of privileges: late_checkout, free_upgrade, priority_seating, etc.');
            
            // Visual
            $table->string('color')->nullable()->comment('Hex color for UI');
            $table->string('icon')->nullable()->comment('Icon name for UI');
            $table->integer('sort_order')->default(0);
            
            $table->boolean('is_active')->default(true);
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->onDelete('cascade');

            $table->index(['loyalty_program_id', 'min_points']);
            $table->index(['loyalty_program_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_tiers');
    }
};
