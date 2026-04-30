<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('source', 50); // beauty, restaurant, etc.
            $table->string('type', 50); // product, service, booking, etc.
            $table->string('status', 50)->default('draft');
            $table->string('title');
            $table->text('description')->nullable();
            
            // Price stored as JSON for Money VO
            $table->json('price');
            
            // Rating stored as JSON for Rating VO
            $table->json('rating')->nullable();
            
            // Metrics
            $table->integer('review_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->integer('order_count')->default(0);
            $table->decimal('conversion_rate', 5, 4)->default(0);
            $table->decimal('popularity_score', 5, 4)->default(0);
            $table->decimal('ranking_score', 5, 4)->default(0);
            
            // Categories and tags stored as JSON
            $table->json('categories')->nullable();
            $table->json('tags')->nullable();
            
            // Images
            $table->json('images')->nullable();
            $table->string('thumbnail')->nullable();
            
            // Attributes stored as JSON
            $table->json('attributes')->nullable();
            
            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->boolean('in_stock')->default(false);
            
            // Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_promoted')->default(false);
            
            // Tenant and source mapping
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->constrained()->onDelete('cascade');
            $table->integer('source_id');
            $table->string('source_type');
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            
            // Metadata stored as JSON
            $table->json('metadata')->nullable();
            
            // Indexes for performance
            $table->index(['status', 'in_stock']);
            $table->index(['source', 'source_id', 'source_type']);
            $table->index('ranking_score');
            $table->index('popularity_score');
            $table->index(['tenant_id', 'status']);
            $table->index('created_at');
            
            // Full-text search index (MySQL specific)
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
