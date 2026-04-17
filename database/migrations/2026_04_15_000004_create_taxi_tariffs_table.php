<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxi_tariffs', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('vehicle_class', ['economy', 'comfort', 'comfort_plus', 'business', 'premium', 'van', 'cargo']);
            $table->string('icon', 10)->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_available_now')->default(true);
            $table->integer('base_price')->default(0);
            $table->integer('price_per_km')->default(0);
            $table->integer('price_per_minute')->default(0);
            $table->integer('minimum_price')->default(0);
            $table->integer('waiting_price_per_minute')->default(0);
            $table->decimal('current_surge_multiplier', 3, 2)->default(1.0);
            $table->decimal('max_surge_multiplier', 3, 2)->default(3.0);
            $table->boolean('fixed_price_available')->default(false);
            $table->boolean('preorder_available')->default(false);
            $table->boolean('split_payment_available')->default(false);
            $table->boolean('corporate_payment_available')->default(false);
            $table->boolean('voice_order_available')->default(false);
            $table->integer('min_vehicle_year')->default(2015);
            $table->decimal('min_vehicle_rating', 3, 2)->default(4.0);
            $table->json('required_features')->nullable();
            $table->integer('passenger_capacity')->default(4);
            $table->integer('luggage_capacity')->default(2);
            $table->integer('average_wait_time_minutes')->default(5);
            $table->integer('max_wait_time_minutes')->default(15);
            $table->integer('available_drivers_count')->default(0);
            $table->boolean('b2b_enabled')->default(false);
            $table->decimal('b2b_discount_percentage', 5, 2)->default(0);
            $table->integer('b2b_monthly_limit')->default(0);
            $table->string('current_promo_code', 50)->nullable();
            $table->integer('current_promo_discount')->nullable();
            $table->timestamp('current_promo_valid_until')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'vehicle_class']);
            $table->index('tenant_id');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_tariffs');
    }
};
