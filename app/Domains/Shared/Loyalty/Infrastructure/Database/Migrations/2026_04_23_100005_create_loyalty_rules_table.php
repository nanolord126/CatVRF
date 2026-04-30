<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('loyalty_program_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Rule type
            $table->enum('type', [
                'order_based',      // Points based on order amount
                'visit_based',      // Points based on visit count
                'item_based',       // Points for specific items
                'time_based',       // Points based on time/day
                'first_visit',      // First visit bonus
                'birthday',         // Birthday bonus
                'referral',         // Referral bonus
                'milestone',        // Milestone bonuses
                'social',           // Social media actions
                'review',           // Review bonus
                'custom'           // Custom rule
            ])->index();
            
            // Conditions (JSON for flexibility)
            $table->json('conditions')->nullable()->comment('Rule conditions: min_amount, day_of_week, items, etc.');
            
            // Point calculation
            $table->enum('calculation_type', [
                'fixed',            // Fixed points
                'percentage',       // Percentage of amount
                'multiplier',       // Multiplier of base points
                'tiered'           // Tiered calculation
            ])->default('percentage');
            
            $table->decimal('points_value', 10, 2)->nullable()->comment('Fixed points or percentage');
            $table->decimal('point_multiplier', 5, 4)->default(1.0000)->comment('Multiplier value');
            
            // Limits
            $table->integer('max_uses_per_guest')->nullable()->comment('Max uses per guest');
            $table->integer('max_uses_total')->nullable()->comment('Max total uses');
            $table->integer('current_uses')->default(0)->comment('Current total uses');
            
            // Schedule
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Targeting
            $table->json('target_tiers')->nullable()->comment('Array of tier slugs this rule applies to');
            $table->json('target_segments')->nullable()->comment('Array of guest segments');
            
            // Priority (higher = checked first)
            $table->integer('priority')->default(0)->index();
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->onDelete('cascade');

            $table->index(['loyalty_program_id', 'is_active']);
            $table->index(['loyalty_program_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rules');
    }
};
