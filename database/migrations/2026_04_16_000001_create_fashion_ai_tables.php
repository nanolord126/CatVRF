<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AI-конструктор Fashion: таблицы для killer-features.
 * PRODUCTION MANDATORY — канон CatVRF 2026.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fashion_webrtc_sessions')) {
            Schema::create('fashion_webrtc_sessions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('stylist_id')->nullable()->constrained()->onDelete('set null');
                $table->string('session_token')->unique();
                $table->enum('status', ['initiated', 'active', 'completed', 'cancelled', 'expired'])->default('initiated');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamp('expires_at');
                $table->string('correlation_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['stylist_id', 'status']);
                $table->comment('WebRTC-сессии с персональными стилистами');
            });
        }

        if (!Schema::hasTable('fashion_virtual_try_on_results')) {
            Schema::create('fashion_virtual_try_on_results', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('design_id')->nullable()->constrained('user_ai_designs')->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade');
                $table->json('try_on_results')->comment('Результаты примерки с fit-score');
                $table->decimal('average_fit_score', 3, 2)->default(0);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->comment('Результаты виртуальной примерки с AR + embeddings');
            });
        }

        if (!Schema::hasTable('fashion_dynamic_pricing')) {
            Schema::create('fashion_dynamic_pricing', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade');
                $table->decimal('base_price', 10, 2)->default(0);
                $table->decimal('dynamic_price', 10, 2)->default(0);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('trend_score', 3, 2)->default(0);
                $table->boolean('is_flash_sale')->default(false);
                $table->timestamp('flash_sale_end_time')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['product_id', 'tenant_id', 'business_group_id']);
                $table->index(['is_flash_sale', 'flash_sale_end_time']);
                $table->comment('Динамическое ценообразование на основе AI-трендов');
            });
        }

        if (!Schema::hasTable('fashion_loyalty_points')) {
            Schema::create('fashion_loyalty_points', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->bigInteger('total_points')->default(0);
                $table->string('tier')->default('standard')->comment('standard, bronze, silver, gold, platinum');
                $table->timestamp('last_earned_at')->nullable();
                $table->timestamp('last_redeemed_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->index(['tier', 'total_points']);
                $table->comment('Loyalty points с gamification и tier-системой');
            });
        }

        if (!Schema::hasTable('fashion_loyalty_transactions')) {
            Schema::create('fashion_loyalty_transactions', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->integer('points_earned')->default(0);
                $table->integer('points_redeemed')->default(0);
                $table->enum('reward_type', ['purchase', 'review', 'referral', 'try_on', 'style_analysis'])->default('purchase');
                $table->string('correlation_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['reward_type', 'created_at']);
                $table->comment('История транзакций loyalty points');
            });
        }

        if (!Schema::hasTable('fashion_nft_avatars')) {
            Schema::create('fashion_nft_avatars', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('avatar_url');
                $table->json('metadata')->comment('style, rarity, generated_at, blockchain_data');
                $table->integer('points_threshold')->default(5000);
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->index(['points_threshold']);
                $table->comment('NFT-цифровые аватары для loyalty gamification');
            });
        }

        if (!Schema::hasTable('fashion_trend_scores')) {
            Schema::create('fashion_trend_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->decimal('trend_score', 3, 2)->default(0);
                $table->decimal('demand_velocity', 3, 2)->default(0);
                $table->enum('demand_trend', ['increasing', 'stable', 'decreasing'])->default('stable');
                $table->decimal('seasonal_factor', 3, 2)->default(1.0);
                $table->decimal('price_elasticity', 5, 2)->default(-1.0);
                $table->decimal('forecast_confidence', 3, 2)->default(0.5);
                $table->string('correlation_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['product_id']);
                $table->index(['trend_score', 'demand_velocity']);
                $table->comment('AI-прогноз трендов для dynamic pricing');
            });
        }

        if (!Schema::hasTable('fashion_social_mentions')) {
            Schema::create('fashion_social_mentions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('fashion_products')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('platform')->comment('instagram, tiktok, twitter, etc.');
                $table->string('external_id')->nullable()->comment('ID поста в социальной сети');
                $table->string('url')->nullable();
                $table->integer('likes')->default(0);
                $table->integer('shares')->default(0);
                $table->integer('comments')->default(0);
                $table->decimal('sentiment_score', 3, 2)->default(0)->comment('AI-анализ тональности');
                $table->string('correlation_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['product_id', 'platform', 'created_at']);
                $table->index(['sentiment_score']);
                $table->comment('Упоминания товаров в соцсетях для trend analysis');
            });
        }

        if (!Schema::hasTable('fashion_stylists')) {
            Schema::create('fashion_stylists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->string('specialization')->comment('casual, business, evening, wedding, etc.');
                $table->json('brands')->nullable()->comment('Бренды, в которых специализируется');
                $table->json('style_preferences')->nullable()->comment('Стиль: minimalist, bohemian, classic, etc.');
                $table->decimal('rating', 3, 2)->default(5.0);
                $table->integer('session_count')->default(0);
                $table->boolean('is_available')->default(true);
                $table->boolean('is_online')->default(false);
                $table->timestamp('last_session_at')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'tenant_id']);
                $table->index(['is_available', 'is_online']);
                $table->index(['rating', 'session_count']);
                $table->comment('Персональные стилисты для WebRTC-консультаций');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fashion_stylists');
        Schema::dropIfExists('fashion_social_mentions');
        Schema::dropIfExists('fashion_trend_scores');
        Schema::dropIfExists('fashion_nft_avatars');
        Schema::dropIfExists('fashion_loyalty_transactions');
        Schema::dropIfExists('fashion_loyalty_points');
        Schema::dropIfExists('fashion_dynamic_pricing');
        Schema::dropIfExists('fashion_virtual_try_on_results');
        Schema::dropIfExists('fashion_webrtc_sessions');
    }
};
