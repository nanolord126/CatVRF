<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confectionery vertical tables — CatVRF 2026
 * Models: ConfectioneryShop, Cake, BakeryOrder
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confectionery_shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('confectionery_cakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('shop_id')->constrained('confectionery_shops')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('flavor')->nullable();
            $table->string('filling')->nullable();
            $table->decimal('price_per_kg', 10, 2);
            $table->decimal('price_b2b', 10, 2)->nullable();
            $table->integer('min_weight_grams')->default(500);
            $table->integer('lead_time_hours')->default(24);
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('confectionery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('confectionery_shops')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->unique()->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_production', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total_price', 14, 2);
            $table->integer('weight_grams');
            $table->text('special_requirements')->nullable();
            $table->timestamp('delivery_at')->nullable();
            $table->boolean('is_b2b')->default(false);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confectionery_orders');
        Schema::dropIfExists('confectionery_cakes');
        Schema::dropIfExists('confectionery_shops');
    }
};
