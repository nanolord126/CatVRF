<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create product_nutrition table for nutritional information and allergens
     */
    public function up(): void
    {
        Schema::create('supermarket_product_nutrition', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('business_group_id')->nullable();
            
            // Basic Nutritional Info (per 100g)
            $table->integer('calories_per_100g')->default(0);
            $table->decimal('proteins_per_100g', 8, 2)->default(0);
            $table->decimal('fats_per_100g', 8, 2)->default(0);
            $table->decimal('carbs_per_100g', 8, 2)->default(0);
            $table->decimal('fiber_per_100g', 8, 2)->default(0);
            $table->decimal('sugar_per_100g', 8, 2)->default(0);
            $table->integer('sodium_per_100g')->default(0);
            
            // Serving Information
            $table->decimal('serving_size', 8, 2)->default(100);
            $table->integer('servings_per_package')->default(1);
            
            // Additional Nutrients
            $table->decimal('saturated_fats_per_100g', 8, 2)->default(0);
            $table->decimal('trans_fats_per_100g', 8, 2)->default(0);
            $table->decimal('cholesterol_per_100g', 8, 2)->default(0);
            $table->integer('potassium_per_100g')->default(0);
            $table->integer('calcium_per_100g')->default(0);
            $table->decimal('iron_per_100g', 8, 2)->default(0);
            $table->integer('vitamin_a_per_100g')->default(0);
            $table->integer('vitamin_c_per_100g')->default(0);
            $table->integer('vitamin_d_per_100g')->default(0);
            
            // Ingredients
            $table->text('ingredients_list')->nullable();
            $table->json('ingredients_json')->nullable();
            
            // Additives
            $table->json('additives')->nullable();
            $table->json('preservatives')->nullable();
            $table->json('colorants')->nullable();
            $table->json('flavor_enhancers')->nullable();
            
            // Mandatory Allergens (152-FZ)
            $table->boolean('contains_gluten')->default(false);
            $table->boolean('contains_crustaceans')->default(false);
            $table->boolean('contains_eggs')->default(false);
            $table->boolean('contains_fish')->default(false);
            $table->boolean('contains_peanuts')->default(false);
            $table->boolean('contains_soy')->default(false);
            $table->boolean('contains_milk')->default(false);
            $table->boolean('contains_nuts')->default(false);
            $table->boolean('contains_celery')->default(false);
            $table->boolean('contains_mustard')->default(false);
            $table->boolean('contains_sesame')->default(false);
            $table->boolean('contains_sulfites')->default(false);
            $table->boolean('contains_lupin')->default(false);
            $table->boolean('contains_molluscs')->default(false);
            
            // Additional Allergens
            $table->boolean('contains_lactose')->default(false);
            $table->boolean('contains_fructose')->default(false);
            $table->boolean('contains_corn')->default(false);
            $table->boolean('contains_yeast')->default(false);
            
            // Allergen Details
            $table->json('allergen_details')->nullable();
            
            // Contraindications
            $table->boolean('diabetic_friendly')->default(false);
            $table->boolean('gluten_free')->default(false);
            $table->boolean('lactose_free')->default(false);
            $table->boolean('low_sodium')->default(false);
            $table->boolean('low_sugar')->default(false);
            $table->boolean('low_fat')->default(false);
            $table->boolean('vegan')->default(false);
            $table->boolean('vegetarian')->default(false);
            $table->boolean('halal')->default(false);
            $table->boolean('kosher')->default(false);
            $table->boolean('organic')->default(false);
            
            // Age Restrictions
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->boolean('pregnancy_warning')->default(false);
            $table->boolean('breastfeeding_warning')->default(false);
            
            // Metadata
            $table->string('nutrition_source')->nullable(); // manufacturer/estimated
            $table->timestamp('nutrition_verified_at')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('product_id');
            $table->index('tenant_id');
            $table->index('business_group_id');
            $table->index('contains_milk');
            $table->index('contains_gluten');
            $table->index('vegan');
            $table->index('vegetarian');
            
            // Foreign Key (if products table exists)
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_product_nutrition');
    }
};
