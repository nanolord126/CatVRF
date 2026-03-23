<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Blogger Profiles
        Schema::create('blogger_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('user_id')->unique();
            $table->foreignId('business_group_id')->nullable()->index();
            $table->string('display_name');
            $table->text('biography')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->index();
            $table->string('inn')->nullable(); // Individual Entrepreneur ID (Russia)
            $table->jsonb('documents')->nullable(); // Stored paths to uploaded documents
            $table->string('primary_category')->nullable(); // beauty, food, auto, etc.
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->unsignedBigInteger('total_streams')->default(0);
            $table->unsignedBigInteger('total_viewers')->default(0);
            $table->decimal('total_earned', 15, 2)->default(0); // Kopiykas
            $table->decimal('wallet_balance', 15, 2)->default(0); // Synced from wallets table
            $table->jsonb('monetization_settings')->nullable(); // CPC/CPM rates
            $table->timestamp('last_stream_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();

            $table->comment('Blogger profiles with verification and monetization settings');
        });

        // Streams (Live Streams)
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('blogger_id')->constrained('blogger_profiles', 'id')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->enum('status', ['scheduled', 'live', 'ended', 'vod', 'archived'])->default('scheduled')->index();
            $table->string('room_id')->unique()->index(); // Reverb room identifier
            $table->string('broadcast_key')->unique()->nullable(); // Secret key for RTMP ingest
            $table->string('broadcast_url')->nullable(); // RTMP URL
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('started_at')->nullable();
            $table->datetime('ended_at')->nullable();
            $table->unsignedBigInteger('viewer_count')->default(0);
            $table->unsignedBigInteger('peak_viewers')->default(0);
            $table->string('duration_seconds')->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0); // Kopiykas
            $table->decimal('platform_commission', 15, 2)->default(0); // 14% of revenue
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->string('hls_playlist_url')->nullable(); // URL to HLS stream for playback
            $table->string('vod_path')->nullable(); // Path to VOD file
            $table->boolean('record_stream')->default(true);
            $table->boolean('allow_chat')->default(true);
            $table->boolean('allow_gifts')->default(true);
            $table->boolean('allow_commerce')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->comment('Live streams by bloggers');
            $table->index(['tenant_id', 'status']);
            $table->index(['blogger_id', 'status']);
        });

        // Stream Products (Products offered during stream)
        Schema::create('stream_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stream_id')->constrained('streams', 'id')->onDelete('cascade');
            $table->foreignId('product_id')->nullable(); // From existing products table
            $table->foreignId('business_group_id')->nullable()->index();
            $table->string('product_name');
            $table->text('product_description')->nullable();
            $table->string('product_image_url')->nullable();
            $table->decimal('price_during_stream', 15, 2); // Override regular price
            $table->decimal('original_price', 15, 2)->nullable();
            $table->unsignedBigInteger('quantity_available')->default(999);
            $table->unsignedBigInteger('quantity_sold')->default(0);
            $table->boolean('is_pinned')->default(false)->index(); // Show in overlay
            $table->integer('pin_position')->nullable(); // Order of pinned products
            $table->enum('sale_type', ['product', 'service', 'subscription', 'bundle'])->default('product');
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('pinned_at')->nullable();
            $table->timestamp('unpinned_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->comment('Products/services showcased during streams');
            $table->index(['stream_id', 'is_pinned']);
        });

        // Stream Orders (Orders placed during stream)
        Schema::create('stream_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stream_id')->constrained('streams', 'id')->onDelete('cascade');
            $table->foreignId('user_id');
            $table->foreignId('business_group_id')->nullable()->index();
            $table->string('order_reference')->unique();
            $table->string('stream_product_id')->nullable();
            $table->enum('status', ['pending', 'paid', 'delivered', 'cancelled'])->default('pending')->index();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->enum('payment_method', ['yuassa', 'sbp', 'wallet', 'card'])->nullable();
            $table->string('payment_id')->nullable();
            $table->string('idempotency_key')->unique()->nullable();
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->comment('Orders created during live streams');
            $table->index(['stream_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Stream Chat Messages
        Schema::create('stream_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stream_id')->constrained('streams', 'id')->onDelete('cascade');
            $table->foreignId('user_id');
            $table->text('message');
            $table->enum('message_type', ['text', 'gift', 'product', 'donation'])->default('text');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->string('moderation_status', 'approved')->default('approved')->index();
            $table->text('moderation_note')->nullable();
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('pinned_at')->nullable();
            $table->timestamps();

            $table->comment('Real-time chat during streams (Reverb + DB sync)');
            $table->index(['stream_id', 'created_at']);
            $table->index(['user_id', 'stream_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stream_chat_messages');
        Schema::dropIfExists('stream_orders');
        Schema::dropIfExists('stream_products');
        Schema::dropIfExists('streams');
        Schema::dropIfExists('blogger_profiles');
    }
};
