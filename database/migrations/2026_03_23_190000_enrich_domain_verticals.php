<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. AGRO DOMAIN (Enrichment)
        if (!Schema::hasTable('agro_crops')) {
            Schema::create('agro_crops', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('farm_id')->constrained('agro_farms')->onDelete('cascade');
                $table->string('name')->comment('Название культуры');
                $table->string('variety')->nullable()->comment('Сорт');
                $table->date('planted_at')->nullable();
                $table->date('harvest_expected_at')->nullable();
                $table->string('status')->default('growing')->index();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 2. SOCIAL NETWORK (Enrichment)
        if (!Schema::hasTable('social_media')) {
            Schema::create('social_media', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->morphs('mediable'); // Связь с постом или профилем
                $table->string('file_path');
                $table->string('mime_type');
                $table->string('processing_status')->default('pending')->index();
                $table->jsonb('meta')->nullable()->comment('EXIF, разрешение, длительность');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('social_follows')) {
            Schema::create('social_follows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
                $table->unique(['follower_id', 'following_id', 'tenant_id']);
                $table->timestamps();
            });
        }

        // 3. CONSTRUCTION (Enrichment)
        if (!Schema::hasTable('construction_estimates')) {
            Schema::create('construction_estimates', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('project_id')->constrained('construction_projects')->onDelete('cascade');
                $table->string('name');
                $table->bigInteger('total_cost_kopeks')->default(0);
                $table->string('status')->default('draft')->index();
                $table->jsonb('items_json')->comment('Список работ и материалов');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // 4. MARKETPLACE / SHOP (Enrichment)
        if (!Schema::hasTable('shop_orders')) {
            Schema::create('shop_orders', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->bigInteger('total_amount_kopeks');
                $table->string('status')->default('pending')->index();
                $table->string('payment_status')->default('unpaid')->index();
                $table->string('shipping_address')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_orders');
        Schema::dropIfExists('construction_estimates');
        Schema::dropIfExists('social_follows');
        Schema::dropIfExists('social_media');
        Schema::dropIfExists('agro_crops');
    }
};
