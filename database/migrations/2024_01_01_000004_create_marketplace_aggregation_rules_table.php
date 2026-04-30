<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_aggregation_rules', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->string('source', 50); // beauty, restaurant, etc.
            $table->string('source_entity_type'); // service, dish, etc.
            
            // Flags
            $table->boolean('is_active')->default(true);
            
            // Filters stored as JSON
            $table->json('filters')->nullable();
            
            // Transformations stored as JSON
            $table->json('transformations')->nullable();
            
            // Priority for ordering
            $table->integer('priority')->default(0);
            
            // Mappings stored as JSON
            $table->json('category_mapping')->nullable();
            $table->json('attribute_mapping')->nullable();
            
            // Scheduling
            $table->string('schedule')->nullable(); // Cron expression
            $table->integer('batch_size')->default(100);
            $table->integer('sync_interval_minutes')->default(60);
            $table->boolean('real_time_sync')->default(false);
            
            // Metadata stored as JSON
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('last_sync_at')->nullable();
            
            // Indexes
            $table->index(['source', 'source_entity_type']);
            $table->index(['is_active', 'priority']);
            $table->index('last_sync_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_aggregation_rules');
    }
};
