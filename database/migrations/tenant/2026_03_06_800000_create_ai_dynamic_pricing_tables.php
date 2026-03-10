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
        // 1. Storage for Price Elasticity and Personality Profiles
        Schema::create('customer_ai_pricing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->float('price_elasticity')->default(1.0); // 1.0 is neutral, < 1.0 (inelastic/luxury), > 1.0 (elastic/price-sensitive)
            $table->string('preferred_device')->nullable(); // iOS, Android, Desktop (iOS often correlated with higher WTP)
            $table->float('luxury_affinity')->default(0.0); // 0 to 1
            $table->boolean('is_bargain_hunter')->default(false); // Tendency to use coupons
            $table->string('persona_tag')->nullable(); // 'Budget Traveler', 'Corporate High-Flyer', 'Family First'
            $table->json('vertical_multipliers')->nullable(); // { "taxi": 1.1, "food": 0.9 }
            $table->timestamps();
        });

        // 2. Real-time dynamic pricing requests log for training
        Schema::create('dynamic_price_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('vertical'); // taxi, food, clinic
            $table->decimal('base_price', 15, 2);
            $table->decimal('final_price', 15, 2);
            $table->float('applied_multiplier');
            $table->json('applied_features')->nullable(); // { "loyalty_discount": 0.95, "ios_surge": 1.03 }
            $table->string('correlation_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dynamic_price_calculations');
        Schema::dropIfExists('customer_ai_pricing_profiles');
    }
};
