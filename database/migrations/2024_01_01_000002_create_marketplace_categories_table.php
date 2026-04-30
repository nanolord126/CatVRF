<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->uuid('parent_uuid')->nullable();
            $table->integer('level')->default(1);
            $table->string('path');
            
            // Verticals that support this category
            $table->json('verticals')->nullable();
            
            // Category attributes stored as JSON
            $table->json('attributes')->nullable();
            
            // Flags
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Media
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            
            // Metadata stored as JSON
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            // Indexes
            $table->index('slug');
            $table->index(['parent_uuid', 'level']);
            $table->index(['is_active', 'sort_order']);
            $table->index('path');
            
            // Foreign key for parent
            $table->foreign('parent_uuid')
                  ->references('uuid')
                  ->on('marketplace_categories')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_categories');
    }
};
