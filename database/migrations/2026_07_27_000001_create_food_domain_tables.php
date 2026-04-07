<?php

declare(strict_types=1);

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
        if (!Schema::hasTable('food_restaurants')) {
            Schema::create('food_restaurants', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('address');
                $table->json('contact');
                $table->string('status')->default('pending')->index();
                $table->json('schedule')->nullable();
                $table->float('rating')->default(0);
                $table->unsignedInteger('review_count')->default(0);
                $table->uuid('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Restaurants in the Food domain');
            });
        }

        if (!Schema::hasTable('food_menu_sections')) {
            Schema::create('food_menu_sections', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('restaurant_id')->constrained('food_restaurants')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();

                $table->comment('Menu sections for a restaurant');
            });
        }

        if (!Schema::hasTable('food_dishes')) {
            Schema::create('food_dishes', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('menu_section_id')->constrained('food_menu_sections')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('price_amount');
                $table->string('price_currency', 3);
                $table->boolean('is_available')->default(true);
                $table->jsonb('allergens')->nullable();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Dishes in a menu section');
            });
        }

        if (!Schema::hasTable('food_modifiers')) {
            Schema::create('food_modifiers', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('dish_id')->constrained('food_dishes')->onDelete('cascade');
                $table->string('name');
                $table->unsignedInteger('price_amount');
                $table->string('price_currency', 3);
                $table->boolean('is_available')->default(true);
                $table->timestamps();

                $table->comment('Modifiers for a dish');
            });
        }

        if (!Schema::hasTable('food_orders')) {
            Schema::create('food_orders', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->foreignUuid('restaurant_id')->constrained('food_restaurants')->onDelete('cascade');
                $table->uuid('client_id')->index();
                $table->unsignedInteger('total_price');
                $table->string('currency', 3);
                $table->string('status')->default('pending')->index();
                $table->uuid('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();

                $table->comment('Orders in the Food domain');
            });
        }

        if (!Schema::hasTable('food_order_items')) {
            Schema::create('food_order_items', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('order_id')->constrained('food_orders')->onDelete('cascade');
                $table->uuid('dish_id');
                $table->string('dish_name');
                $table->unsignedInteger('quantity');
                $table->unsignedInteger('unit_price');
                $table->jsonb('modifiers')->nullable();
                $table->unsignedInteger('total_price');

                $table->comment('Items within an order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_order_items');
        Schema::dropIfExists('food_orders');
        Schema::dropIfExists('food_modifiers');
        Schema::dropIfExists('food_dishes');
        Schema::dropIfExists('food_menu_sections');
        Schema::dropIfExists('food_restaurants');
    }
};
