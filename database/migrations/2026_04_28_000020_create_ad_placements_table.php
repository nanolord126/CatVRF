<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create ad_placements table for Advertising vertical
 *
 * Stores ad placements within campaigns with content types
 * and placement zones. Supports banners, video, text ads.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->string('placement_zone', 100)->index()->comment('e.g., marketplace.sidebar, feed.top');
            $table->enum('content_type', ['banner', 'video', 'text', 'native', 'rich_media'])->default('banner');
            $table->json('content')->comment('Ad content (images, text, video URLs)');
            $table->unsignedInteger('priority')->default(50)->comment('Priority for ad selection (0-100)');
            $table->unsignedInteger('max_impressions')->nullable()->comment('Max impressions limit');
            $table->unsignedInteger('current_impressions')->default(0)->comment('Current impression count');
            $table->unsignedInteger('max_clicks')->nullable()->comment('Max clicks limit');
            $table->unsignedInteger('current_clicks')->default(0)->comment('Current click count');
            $table->enum('status', ['active', 'paused', 'completed'])->default('active')->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->json('settings')->nullable()->comment('Placement-specific settings');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('campaign_id')
                ->references('id')
                ->on('ad_campaigns')
                ->onDelete('cascade');

            // Composite indexes for common queries
            $table->index(['campaign_id', 'status'], 'ad_placements_campaign_status_idx');
            $table->index(['placement_zone', 'status'], 'ad_placements_zone_status_idx');
            $table->index(['content_type', 'status'], 'ad_placements_type_status_idx');
            $table->index(['campaign_id', 'placement_zone'], 'ad_placements_campaign_zone_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_placements');
    }
};
