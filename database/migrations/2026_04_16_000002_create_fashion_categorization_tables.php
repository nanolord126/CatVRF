<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fashion Categorization & Filtering tables.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fashion_categories')) {
            Schema::create('fashion_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->foreignId('parent_category')->nullable()->constrained('fashion_categories')->onDelete('cascade');
                $table->string('icon')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'parent_category']);
                $table->comment('Fashion category hierarchy');
            });
        }

        if (!Schema::hasTable('fashion_product_categories')) {
            Schema::create('fashion_product_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('primary_category');
                $table->json('secondary_categories')->nullable();
                $table->json('tags')->nullable();
                $table->string('style_profile')->nullable();
                $table->string('season')->nullable();
                $table->string('target_audience')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['product_id', 'tenant_id']);
                $table->index(['tenant_id', 'primary_category']);
                $table->comment('Product categorization with ML attributes');
            });
        }

        if (!Schema::hasTable('fashion_user_filter_preferences')) {
            Schema::create('fashion_user_filter_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->json('preferred_filters')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->comment('User filter preferences for personalization');
            });
        }

        if (!Schema::hasTable('fashion_user_memory_interactions')) {
            Schema::create('fashion_user_memory_interactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->enum('interaction_type', ['view', 'add_to_cart', 'add_to_wishlist', 'purchase', 'return', 'review', 'share']);
                $table->decimal('interaction_score', 3, 2)->default(0);
                $table->string('category')->nullable();
                $table->string('brand')->nullable();
                $table->integer('price')->default(0);
                $table->string('style_profile')->nullable();
                $table->string('color')->nullable();
                $table->json('context')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'tenant_id', 'created_at']);
                $table->index(['user_id', 'product_id']);
                $table->index(['tenant_id', 'category']);
                $table->comment('User interaction memory for ML pattern analysis');
            });
        }

        if (!Schema::hasTable('fashion_user_memory_patterns')) {
            Schema::create('fashion_user_memory_patterns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->enum('pattern_type', ['preferred_categories', 'preferred_brands', 'price_range', 'preferred_styles', 'preferred_colors', 'shopping_frequency', 'peak_shopping_hours', 'session_duration', 'conversion_rate']);
                $table->json('pattern_value')->nullable();
                $table->decimal('confidence', 3, 2)->default(0);
                $table->integer('sample_size')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id', 'pattern_type']);
                $table->index(['tenant_id', 'pattern_type']);
                $table->comment('Extracted ML patterns from user behavior');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_user_memory_patterns');
        Schema::dropIfExists('fashion_user_memory_interactions');
        Schema::dropIfExists('fashion_user_filter_preferences');
        Schema::dropIfExists('fashion_product_categories');
        Schema::dropIfExists('fashion_categories');
    }
};
