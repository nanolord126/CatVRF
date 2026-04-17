<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Skip this migration for SQLite - table created in later migration 2026_03_25_000002
        // and SQLite has issues with migration ordering
        if (config('database.default') !== 'sqlite') {
            Schema::table('electronics_products', function (Blueprint $table) {
                $table->index(['brand', 'category'], 'brand_category_idx');
                $table->index(['price_kopecks', 'availability_status'], 'price_stock_idx');
                $table->index(['category', 'rating'], 'category_rating_idx');
                $table->index(['color', 'availability_status'], 'color_stock_idx');
                $table->index('is_bestseller');
                $table->index('views_count');
                $table->index('created_at');
                $table->index('name');
                $table->index('brand');
                $table->index('category');
                // SQLite doesn't support fulltext, using regular indexes instead
                // $table->fullText(['name', 'brand', 'category'], 'search_fulltext_idx');
            });

            // PostgreSQL GIN index not supported in SQLite
            // DB::statement('CREATE INDEX electronics_specs_gin ON electronics_products USING GIN (specs)');
        }
    }

    public function down(): void
    {
        Schema::table('electronics_products', function (Blueprint $table) {
            $table->dropIndex('brand_category_idx');
            $table->dropIndex('price_stock_idx');
            $table->dropIndex('category_rating_idx');
            $table->dropIndex('color_stock_idx');
            $table->dropIndex(['is_bestseller']);
            $table->dropIndex(['views_count']);
            $table->dropIndex(['created_at']);
            $table->dropIndex('search_fulltext_idx');
        });

        DB::statement('DROP INDEX IF EXISTS electronics_specs_gin ON electronics_products');
    }
};
