<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurants')) {
            return;
        }

        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->geometry('geo_point')->nullable()->spatialIndex();
            $table->json('cuisine_types')->nullable()->comment('["Italian", "Asian", "Fast-food"]');
            $table->json('schedule')->nullable()->comment('Operating hours');
            $table->float('rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->boolean('is_verified')->default(false)->index();
            $table->integer('commission_rate')->default(1400)->comment('14%');
            $table->integer('average_delivery_time')->nullable()->comment('in minutes');
            $table->integer('minimum_order_amount')->default(500)->comment('in kopeks');
            $table->json('delivery_zones')->nullable()->comment('GeoJSON polygons or radius');
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'is_verified']);
            $table->comment('Restaurants with cuisine types and delivery zones');
        });

        Schema::create('restaurant_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->comment('Breakfast, Lunch, Dinner, Desserts');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['restaurant_id', 'name']);
            $table->comment('Menu categories/sections');
        });

        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('restaurant_menus')->onDelete('cascade')->index();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('price')->comment('in kopeks');
            $table->integer('calories')->nullable();
            $table->json('allergens')->nullable()->comment('["gluten", "dairy", "nuts"]');
            $table->integer('preparation_time')->nullable()->comment('in minutes');
            $table->json('consumables')->nullable()->comment('{"ingredient_id": quantity, ...}');
            $table->boolean('is_available')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['restaurant_id', 'is_available']);
            $table->comment('Dishes with allergies, calories, preparation time');
        });

        Schema::create('dish_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->comment('Small, Medium, Large or specific variants');
            $table->integer('price_modifier')->default(0)->comment('in kopeks');
            $table->timestamps();

            $table->unique(['dish_id', 'name']);
            $table->comment('Dish size/option variants');
        });

        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->enum('order_type', ['dine_in', 'takeout', 'delivery'])->default('delivery')->index();
            $table->enum('status', ['pending', 'cooking', 'ready', 'delivered', 'cancelled'])->default('pending')->index();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->index();
            $table->integer('total_price')->comment('in kopeks');
            $table->integer('delivery_fee')->nullable()->comment('in kopeks');
            $table->integer('discount_amount')->default(0);
            $table->string('delivery_address')->nullable();
            $table->geometry('delivery_point')->nullable();
            $table->integer('surge_multiplier')->nullable()->comment('for delivery surge');
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->dateTime('hold_until')->nullable()->comment('20-min hold');
            $table->text('customer_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['restaurant_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->comment('Restaurant orders with delivery tracking');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade')->index();
            $table->foreignId('dish_id')->constrained('dishes')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('quantity');
            $table->integer('unit_price')->comment('in kopeks at order time');
            $table->json('selected_variants')->nullable()->comment('{"variant_id": "Medium", ...}');
            $table->text('special_instructions')->nullable();
            $table->timestamps();

            $table->comment('Individual items in order');
        });

        Schema::create('kds_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade')->index();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->enum('status', ['pending', 'in_progress', 'ready', 'picked_up'])->default('pending')->index();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ready_at')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->integer('priority')->default(0)->comment('0=normal, 1=express');
            $table->string('correlation_id')->nullable()->index();
            $table->json('tickets')->nullable()->comment('per cooking station');
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
            $table->comment('Kitchen Display System orders');
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('restaurant_orders')->onDelete('cascade')->index();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('delivery_partner_id')->nullable()->constrained('users')->onDelete('set null')->index();
            $table->string('delivery_address');
            $table->geometry('delivery_point')->nullable();
            $table->enum('status', ['pending', 'assigned', 'picked_up', 'delivered', 'failed'])->default('pending')->index();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('picked_up_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->integer('estimated_minutes')->nullable();
            $table->integer('surge_multiplier')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
            $table->comment('Delivery tracking per order');
        });

        Schema::create('delivery_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->comment('Downtown, Suburbs, etc.');
            $table->geometry('polygon')->spatialIndex()->comment('GeoJSON polygon for zone coverage');
            $table->integer('base_delivery_fee')->comment('in kopeks');
            $table->integer('surge_multiplier')->nullable()->comment('1000 = 100%, 2000 = 200%');
            $table->timestamps();

            $table->index(['restaurant_id']);
            $table->comment('Delivery zones with surge pricing');
        });

        Schema::create('restaurant_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('rating')->comment('1-5');
            $table->text('comment');
            $table->json('photos')->nullable();
            $table->integer('food_quality')->nullable();
            $table->integer('delivery_speed')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['restaurant_id', 'created_at']);
            $table->comment('Restaurant reviews');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_reviews');
        Schema::dropIfExists('delivery_zones');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('kds_orders');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('restaurant_orders');
        Schema::dropIfExists('dish_variants');
        Schema::dropIfExists('dishes');
        Schema::dropIfExists('restaurant_menus');
        Schema::dropIfExists('restaurants');
    }
};


