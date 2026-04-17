<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fashion Advanced Services tables.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Collaborative Filtering
        if (!Schema::hasTable('fashion_user_latent_factors')) {
            Schema::create('fashion_user_latent_factors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->json('factors');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->comment('User latent factors for matrix factorization');
            });
        }

        if (!Schema::hasTable('fashion_item_latent_factors')) {
            Schema::create('fashion_item_latent_factors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->json('factors');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['product_id', 'tenant_id']);
                $table->comment('Item latent factors for matrix factorization');
            });
        }

        // Social Media Trends
        if (!Schema::hasTable('fashion_trend_keywords')) {
            Schema::create('fashion_trend_keywords', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('keyword');
                $table->enum('type', ['hashtag', 'keyword']);
                $table->enum('platform', ['instagram', 'tiktok', 'pinterest', 'twitter']);
                $table->decimal('trend_score', 3, 2)->default(0);
                $table->decimal('velocity', 3, 2)->default(0);
                $table->string('category')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'platform', 'created_at']);
                $table->comment('Social media trend keywords');
            });
        }

        if (!Schema::hasTable('fashion_social_mentions')) {
            Schema::create('fashion_social_mentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->enum('platform', ['instagram', 'tiktok', 'pinterest', 'twitter']);
                $table->text('content')->nullable();
                $table->integer('likes')->default(0);
                $table->integer('shares')->default(0);
                $table->integer('comments')->default(0);
                $table->decimal('sentiment_score', 3, 2)->default(0.5);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'product_id', 'created_at']);
                $table->comment('Social media mentions of products');
            });
        }

        if (!Schema::hasTable('fashion_influencers')) {
            Schema::create('fashion_influencers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->enum('platform', ['instagram', 'tiktok', 'pinterest', 'twitter']);
                $table->string('handle')->unique();
                $table->integer('followers_count')->default(0);
                $table->decimal('engagement_rate', 5, 2)->default(0);
                $table->string('category')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'category']);
                $table->comment('Fashion influencers for collaboration');
            });
        }

        if (!Schema::hasTable('fashion_trend_scores')) {
            Schema::create('fashion_trend_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->decimal('trend_score', 3, 2)->default(0);
                $table->decimal('demand_velocity', 3, 2)->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique('product_id');
                $table->comment('Product trend scores from social media');
            });
        }

        // Review Moderation
        if (!Schema::hasTable('fashion_review_moderations')) {
            Schema::create('fashion_review_moderations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('review_id')->constrained('fashion_reviews')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->decimal('spam_score', 3, 2)->default(0);
                $table->decimal('toxicity_score', 3, 2)->default(0);
                $table->decimal('fake_score', 3, 2)->default(0);
                $table->enum('sentiment', ['positive', 'negative', 'neutral'])->default('neutral');
                $table->enum('action', ['approve', 'reject', 'flag'])->default('flag');
                $table->boolean('manual_review_required')->default(false);
                $table->timestamp('moderated_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['review_id', 'tenant_id']);
                $table->comment('Review moderation results');
            });
        }

        // Visual Search
        if (!Schema::hasTable('fashion_product_embeddings')) {
            Schema::create('fashion_product_embeddings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->json('embedding');
                $table->integer('embedding_dimension')->default(512);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['product_id', 'tenant_id']);
                $table->comment('Product image embeddings for visual search');
            });
        }

        if (!Schema::hasTable('fashion_visual_searches')) {
            Schema::create('fashion_visual_searches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->text('image_url');
                $table->json('embedding');
                $table->timestamp('searched_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'searched_at']);
                $table->comment('Visual search history');
            });
        }

        // Size Recommendation
        if (!Schema::hasTable('fashion_user_size_profiles')) {
            Schema::create('fashion_user_size_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->integer('height')->nullable();
                $table->integer('weight')->nullable();
                $table->integer('chest')->nullable();
                $table->integer('waist')->nullable();
                $table->integer('hips')->nullable();
                $table->integer('shoe_size')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->comment('User size measurement profiles');
            });
        }

        if (!Schema::hasTable('fashion_brand_fit_profiles')) {
            Schema::create('fashion_brand_fit_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('brand');
                $table->decimal('runs_small', 3, 2)->default(0);
                $table->decimal('runs_large', 3, 2)->default(0);
                $table->decimal('true_to_size', 3, 2)->default(1.0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['tenant_id', 'brand']);
                $table->comment('Brand size fit characteristics');
            });
        }

        if (!Schema::hasTable('fashion_size_recommendations')) {
            Schema::create('fashion_size_recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->string('recommended_size');
                $table->decimal('confidence', 3, 2)->default(0);
                $table->timestamp('recommended_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'product_id']);
                $table->comment('Size recommendation history');
            });
        }

        // Inventory Forecasting
        if (!Schema::hasTable('fashion_demand_forecasts')) {
            Schema::create('fashion_demand_forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->json('forecast_data');
                $table->timestamp('forecasted_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['product_id', 'tenant_id']);
                $table->comment('Demand forecasts for products');
            });
        }

        if (!Schema::hasTable('fashion_out_of_stock_events')) {
            Schema::create('fashion_out_of_stock_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->integer('estimated_lost_sales')->default(0);
                $table->integer('duration_hours')->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'product_id', 'created_at']);
                $table->comment('Out of stock events tracking');
            });
        }

        // A/B Price Testing
        if (!Schema::hasTable('fashion_ab_price_tests')) {
            Schema::create('fashion_ab_price_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->decimal('control_price', 10, 2);
                $table->decimal('test_price', 10, 2);
                $table->integer('control_group_size')->default(0);
                $table->integer('test_group_size')->default(0);
                $table->integer('control_conversions')->default(0);
                $table->integer('test_conversions')->default(0);
                $table->decimal('control_revenue', 12, 2)->default(0);
                $table->decimal('test_revenue', 12, 2)->default(0);
                $table->enum('status', ['draft', 'active', 'completed'])->default('draft');
                $table->string('winner')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->comment('A/B price testing experiments');
            });
        }

        if (!Schema::hasTable('fashion_ab_price_test_conversions')) {
            Schema::create('fashion_ab_price_test_conversions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('test_id')->constrained('fashion_ab_price_tests')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('group', ['control', 'test']);
                $table->decimal('price', 10, 2);
                $table->timestamp('converted_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['test_id', 'user_id']);
                $table->comment('A/B test conversion tracking');
            });
        }

        // Email Campaigns
        if (!Schema::hasTable('fashion_email_campaigns')) {
            Schema::create('fashion_email_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('subject');
                $table->text('template');
                $table->json('segmentation_rules')->nullable();
                $table->enum('trigger_type', ['abandoned_cart', 'welcome', 'reengagement', 'promotion'])->nullable();
                $table->json('trigger_config')->nullable();
                $table->enum('status', ['draft', 'scheduled', 'sent', 'active'])->default('draft');
                $table->timestamp('scheduled_for')->nullable();
                $table->integer('sent_count')->default(0);
                $table->integer('opened_count')->default(0);
                $table->integer('clicked_count')->default(0);
                $table->integer('converted_count')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->comment('Email campaigns');
            });
        }

        if (!Schema::hasTable('fashion_email_opens')) {
            Schema::create('fashion_email_opens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('fashion_email_campaigns')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('opened_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['campaign_id', 'user_id']);
                $table->comment('Email open tracking');
            });
        }

        if (!Schema::hasTable('fashion_email_clicks')) {
            Schema::create('fashion_email_clicks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('fashion_email_campaigns')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->text('link');
                $table->timestamp('clicked_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['campaign_id', 'user_id']);
                $table->comment('Email click tracking');
            });
        }

        if (!Schema::hasTable('fashion_email_logs')) {
            Schema::create('fashion_email_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained('fashion_email_campaigns')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('subject');
                $table->text('content');
                $table->timestamp('sent_at');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['campaign_id', 'user_id']);
                $table->comment('Email send logs');
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'fashion_email_logs',
            'fashion_email_clicks',
            'fashion_email_opens',
            'fashion_email_campaigns',
            'fashion_ab_price_test_conversions',
            'fashion_ab_price_tests',
            'fashion_out_of_stock_events',
            'fashion_demand_forecasts',
            'fashion_size_recommendations',
            'fashion_brand_fit_profiles',
            'fashion_user_size_profiles',
            'fashion_visual_searches',
            'fashion_product_embeddings',
            'fashion_review_moderations',
            'fashion_trend_scores',
            'fashion_influencers',
            'fashion_social_mentions',
            'fashion_trend_keywords',
            'fashion_item_latent_factors',
            'fashion_user_latent_factors',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
