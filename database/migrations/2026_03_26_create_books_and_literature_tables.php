<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for BooksAndLiterature vertical (Layer 1/9)
 * Total of 7 tables covering B2C/B2B books, genres, authors, and subscriptions.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Authors Table
        if (!Schema::hasTable('book_authors')) {
            Schema::create('book_authors', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->text('biography')->nullable();
                $table->string('nationality')->nullable();
                $table->date('birth_date')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->comment('Authors directory with biological and professional data');
            });
        }

        // 2. Genres Table
        if (!Schema::hasTable('book_genres')) {
            Schema::create('book_genres', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->integer('popularity_index')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Literary genres for filtering and AI matching');
            });
        }

        // 3. Book Stores (Physical/Digital points of sale)
        if (!Schema::hasTable('book_stores')) {
            Schema::create('book_stores', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->string('address')->nullable();
                $table->string('contact_phone')->nullable();
                $table->boolean('has_lounge')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Bookstores or libraries linked to tenants');
            });
        }

        // 4. Books Table
        if (!Schema::hasTable('books')) {
            Schema::create('books', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('store_id')->constrained('book_stores');
                $table->foreignId('author_id')->constrained('book_authors');
                $table->foreignId('genre_id')->constrained('book_genres');
                $table->string('title')->index();
                $table->string('isbn', 20)->unique()->index();
                $table->text('description')->nullable();
                $table->enum('format', ['hardcover', 'paperback', 'audio', 'digital', 'collectible'])->default('paperback');
                $table->integer('price_b2c')->default(0); // Regular retail (kopecks)
                $table->integer('price_b2b')->default(0); // Corporate/Institutional wholesale (kopecks)
                $table->integer('stock_quantity')->default(0);
                $table->integer('page_count')->nullable();
                $table->string('language', 10)->default('ru');
                $table->jsonb('metadata')->nullable(); // Mood, Difficulty, Age rating
                $table->jsonb('tags')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active', 'format']);
                $table->comment('Primary books table for B2C/B2B catalog');
            });
        }

        // 5. Subscription Boxes
        if (!Schema::hasTable('book_subscription_boxes')) {
            Schema::create('book_subscription_boxes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name'); // e.g. "Monthly Fantasy Surprise"
                $table->text('description');
                $table->integer('price_monthly')->default(0);
                $table->jsonb('genre_focus')->nullable();
                $table->integer('items_per_box')->default(1);
                $table->boolean('is_giftable')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Curated monthly book boxes for recurring revenue');
            });
        }

        // 6. Orders and Corporate Orders
        if (!Schema::hasTable('book_orders')) {
            Schema::create('book_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('type', ['b2c', 'b2b', 'institutional', 'subscription'])->default('b2c');
                $table->string('order_number')->unique();
                $table->integer('total_amount')->default(0);
                $table->string('status')->default('pending'); // pending, processing, shipped, delivered, returned, cancelled
                $table->text('shipping_address')->nullable();
                $table->jsonb('order_items')->nullable(); // [{book_id, quantity, price_at_buy}]
                $table->boolean('is_gift')->default(false);
                $table->string('gift_message')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'user_id', 'status']);
                $table->comment('Combined B2C/B2B order tracker');
            });
        }

        // 7. Literary Reviews and AI Training Data
        if (!Schema::hasTable('book_reviews')) {
            Schema::create('book_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('book_id')->constrained('books');
                $table->foreignId('user_id')->constrained('users');
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->jsonb('mood_tags')->nullable(); // AI-extracted mood
                $table->boolean('is_verified_purchase')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('User reviews for social proof and AI recommendations');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('book_reviews');
        Schema::dropIfExists('book_orders');
        Schema::dropIfExists('book_subscription_boxes');
        Schema::dropIfExists('books');
        Schema::dropIfExists('book_stores');
        Schema::dropIfExists('book_genres');
        Schema::dropIfExists('book_authors');
    }
};
