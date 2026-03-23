<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // NFT Gifts (TON Blockchain)
        Schema::create('nft_gifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stream_id')->constrained('streams', 'id')->onDelete('cascade');
            $table->foreignId('sender_user_id'); // Who sent the gift
            $table->foreignId('recipient_user_id'); // Blogger or viewer
            $table->foreignId('business_group_id')->nullable()->index();
            $table->string('gift_name');
            $table->string('gift_image_url');
            $table->text('gift_description')->nullable();
            $table->decimal('gift_price', 15, 2); // Kopiykas
            $table->enum('gift_type', ['emoji', 'frame', 'sticker', 'collectible'])->default('emoji');
            $table->string('ton_address')->nullable(); // Recipient's TON wallet address
            $table->string('nft_contract_address')->nullable(); // TON NFT contract
            $table->string('nft_address')->nullable(); // Individual NFT address
            $table->string('nft_token_id')->nullable();
            $table->string('metadata_uri')->nullable(); // IPFS or central storage
            $table->jsonb('metadata')->nullable(); // Embedded metadata
            $table->enum('minting_status', ['pending', 'minting', 'minted', 'failed', 'expired'])->default('pending')->index();
            $table->text('minting_error')->nullable();
            $table->string('ton_tx_hash')->nullable(); // Transaction hash on TON
            $table->timestamp('minted_at')->nullable();
            $table->timestamp('upgrade_eligible_at')->nullable(); // After 14 days, upgrade to collector
            $table->boolean('is_upgraded')->default(false);
            $table->timestamp('upgraded_at')->nullable();
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_id')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->comment('NFT gifts sent during streams (TON blockchain)');
            $table->index(['stream_id', 'recipient_user_id']);
            $table->index(['sender_user_id', 'created_at']);
            $table->index(['minting_status', 'created_at']);
        });

        // NFT Gift Collections (Seasonal, limited editions)
        Schema::create('nft_gift_collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->string('collection_name');
            $table->text('collection_description')->nullable();
            $table->string('collection_image_url')->nullable();
            $table->string('ton_collection_address')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedBigInteger('max_supply')->nullable();
            $table->unsignedBigInteger('minted_count')->default(0);
            $table->jsonb('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('Collections of NFT gifts (seasonal, limited editions)');
        });

        // Streaming Statistics
        Schema::create('stream_statistics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('stream_id')->constrained('streams', 'id')->onDelete('cascade');
            $table->unsignedBigInteger('unique_viewers')->default(0);
            $table->unsignedBigInteger('total_messages')->default(0);
            $table->unsignedBigInteger('total_gifts')->default(0);
            $table->unsignedBigInteger('total_gifts_revenue')->default(0); // Kopiykas
            $table->unsignedBigInteger('total_products_sold')->default(0);
            $table->unsignedBigInteger('total_commerce_revenue')->default(0); // Kopiykas
            $table->decimal('average_session_duration', 8, 2)->default(0); // Minutes
            $table->decimal('engagement_rate', 5, 2)->default(0); // Percentage
            $table->jsonb('viewer_countries')->nullable(); // Geo distribution
            $table->jsonb('traffic_sources')->nullable(); // Referrers
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('Stream analytics and metrics');
            $table->unique(['stream_id']);
        });

        // Blogger Verification Documents
        Schema::create('blogger_verification_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->nullable()->index();
            $table->foreignId('tenant_id')->index();
            $table->foreignId('blogger_id')->constrained('blogger_profiles', 'id')->onDelete('cascade');
            $table->enum('document_type', ['passport', 'inn_certificate', 'business_license', 'bank_statement', 'other'])->index();
            $table->string('file_path'); // Storage path
            $table->enum('verification_status', ['pending', 'approved', 'rejected'])->default('pending')->index();
            $table->text('verification_note')->nullable();
            $table->string('verified_by')->nullable(); // Admin username
            $table->timestamp('verified_at')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('Documents for blogger verification (INN, license, etc.)');
            $table->index(['blogger_id', 'verification_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogger_verification_documents');
        Schema::dropIfExists('stream_statistics');
        Schema::dropIfExists('nft_gift_collections');
        Schema::dropIfExists('nft_gifts');
    }
};
