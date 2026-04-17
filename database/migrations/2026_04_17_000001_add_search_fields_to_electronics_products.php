<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electronics_products', function (Blueprint $table) {
            // Color for filtering
            $table->string('color')->nullable()->index()->after('specs');
            
            // Images array
            $table->jsonb('images')->nullable()->after('color');
            
            // Rating and reviews
            $table->decimal('rating', 3, 2)->default(0)->after('images');
            $table->integer('reviews_count')->unsigned()->default(0)->after('rating');
            
            // Active status
            $table->boolean('is_active')->default(true)->index()->after('reviews_count');
            
            // Availability status (more detailed than availability enum)
            $table->enum('availability_status', ['in_stock', 'low_stock', 'out_of_stock', 'pre_order', 'discontinued'])
                  ->default('in_stock')
                  ->index()
                  ->after('is_active');
            
            // Stock quantity alias for consistency
            $table->integer('stock_quantity')->default(0)->after('availability_status');
            
            // Views tracking
            $table->integer('views_count')->unsigned()->default(0)->after('stock_quantity');
            
            // Bestseller flag
            $table->boolean('is_bestseller')->default(false)->index()->after('views_count');
            
            // Original price for discount calculation
            $table->integer('original_price_kopecks')->unsigned()->nullable()->after('price_kopecks');
            
            // Category as string for easier filtering
            $table->string('category')->nullable()->index()->after('brand');
        });
        
        // Add indexes for better search performance
        Schema::table('electronics_products', function (Blueprint $table) {
            $table->index(['brand', 'category']);
            $table->index(['price_kopecks', 'rating']);
            $table->index(['is_active', 'availability_status']);
        });
    }

    public function down(): void
    {
        Schema::table('electronics_products', function (Blueprint $table) {
            $table->dropIndex(['brand', 'category']);
            $table->dropIndex(['price_kopecks', 'rating']);
            $table->dropIndex(['is_active', 'availability_status']);
            
            $table->dropColumn([
                'category',
                'original_price_kopecks',
                'is_bestseller',
                'views_count',
                'stock_quantity',
                'availability_status',
                'is_active',
                'reviews_count',
                'rating',
                'images',
                'color',
            ]);
        });
    }
};
