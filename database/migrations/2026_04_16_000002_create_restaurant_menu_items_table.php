<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('restaurant_id')->constrained('restaurants')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('profit_margin', 5, 2)->nullable();
            $table->integer('calories')->nullable();
            $table->decimal('protein_g', 8, 2)->nullable();
            $table->decimal('fat_g', 8, 2)->nullable();
            $table->decimal('carbohydrates_g', 8, 2)->nullable();
            $table->decimal('fiber_g', 8, 2)->nullable();
            $table->decimal('sugar_g', 8, 2)->nullable();
            $table->decimal('sodium_mg', 8, 2)->nullable();
            $table->json('ingredients')->nullable();
            $table->json('allergens')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_vegan')->default(false);
            $table->boolean('is_gluten_free')->default(false);
            $table->boolean('is_spicy')->default(false);
            $table->integer('preparation_time_minutes')->nullable();
            $table->string('image_url')->nullable();
            $table->string('ar_preview_url')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['tenant_id', 'restaurant_id', 'is_available']);
            $table->index(['restaurant_id', 'category']);
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_menu_items');
    }
};
