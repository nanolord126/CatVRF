<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create ad_creatives table for Advertising vertical
 *
 * Stores AI-generated and manually created ad creatives
 * with A/B testing support and performance metrics.
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
        Schema::create('ad_creatives', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('Creator user ID');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['banner', 'video', 'text', 'native', 'rich_media'])->default('banner');
            $table->string('headline', 255)->nullable();
            $table->text('body_text')->nullable();
            $table->string('call_to_action', 100)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->json('assets')->nullable()->comment('Creative assets (images, videos, etc.)');
            $table->json('ai_generated_data')->nullable()->comment('AI-generated content and metadata');
            $table->string('ab_test_group', 50)->nullable()->index()->comment('A/B test group identifier');
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->decimal('ctr', 5, 4)->default(0)->comment('Click-through rate');
            $table->decimal('conversion_rate', 5, 4)->default(0)->comment('Conversion rate');
            $table->enum('status', ['draft', 'active', 'paused', 'archived'])->default('draft')->index();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('campaign_id')
                ->references('id')
                ->on('ad_campaigns')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Composite indexes for performance
            $table->index(['campaign_id', 'status'], 'ad_creatives_campaign_status_idx');
            $table->index(['ab_test_group', 'status'], 'ad_creatives_abtest_status_idx');
            $table->index(['status', 'ctr'], 'ad_creatives_status_ctr_idx');
            $table->index(['campaign_id', 'ab_test_group'], 'ad_creatives_campaign_abtest_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_creatives');
    }
};
