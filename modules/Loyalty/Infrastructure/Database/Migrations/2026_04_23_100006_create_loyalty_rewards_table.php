<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('loyalty_program_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            // Reward type
            $table->enum('type', [
                'discount',         // Discount on order
                'free_item',        // Free menu item/service
                'upgrade',          // Upgrade (room, table, etc.)
                'service',          // Free service (spa, breakfast, etc.)
                'cashback',         // Cashback to balance
                'voucher',          // Voucher/coupon
                'privilege',        // Special privilege
                'custom'           // Custom reward
            ])->index();
            
            // Cost in points
            $table->decimal('points_cost', 10, 2)->default(0)->comment('Points required to redeem');
            
            // Value
            $table->enum('value_type', [
                'fixed',            // Fixed value (e.g., 500 rubles)
                'percentage',       // Percentage (e.g., 10% off)
                'item'             // Specific item/service
            ])->default('fixed');
            
            $table->decimal('value_amount', 10, 2)->nullable()->comment('Fixed amount or percentage');
            $table->unsignedBigInteger('menu_item_id')->nullable()->comment('For free_item type');
            $table->string('item_code')->nullable()->comment('For item-based rewards');
            
            // Conditions
            $table->json('conditions')->nullable()->comment('Conditions: min_order, day_of_week, etc.');
            
            // Availability
            $table->integer('stock_quantity')->nullable()->comment('Limited quantity rewards');
            $table->integer('redeemed_count')->default(0)->comment('Times redeemed');
            
            // Targeting
            $table->json('target_tiers')->nullable()->comment('Array of tier slugs eligible');
            $table->json('target_segments')->nullable()->comment('Array of guest segments');
            
            // Validity
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Redemption settings
            $table->integer('max_redemptions_per_guest')->nullable()->comment('Limit per guest');
            $table->integer('max_redemptions_total')->nullable()->comment('Total limit');
            
            // Visual
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->onDelete('cascade');

            $table->index(['loyalty_program_id', 'is_active']);
            $table->index(['loyalty_program_id', 'points_cost']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
