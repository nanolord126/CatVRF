<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OfficeCatering vertical tables — CatVRF 2026
 * Models: CateringCompany, CateringMenu, CateringOrder, CorporateClient, CorporateOrder, OfficeMenu
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lon', 11, 8)->nullable();
            $table->integer('min_persons')->default(5);
            $table->integer('max_persons')->nullable();
            $table->decimal('price_per_person', 10, 2);
            $table->decimal('price_b2b_per_person', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('catering_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('catering_companies')->onDelete('cascade');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('name');
            $table->string('type'); // breakfast, lunch, dinner, coffee_break
            $table->decimal('price_per_person', 10, 2);
            $table->json('dishes')->nullable();
            $table->json('dietary_options')->nullable(); // vegetarian, vegan, halal, gluten_free
            $table->boolean('is_active')->default(true);
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'type', 'is_active']);
        });

        Schema::create('catering_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained('catering_companies')->onDelete('cascade');
            $table->foreignId('menu_id')->constrained('catering_menus')->onDelete('restrict');
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->unique()->nullable();
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'delivering', 'delivered', 'cancelled'])->default('pending');
            $table->integer('persons_count');
            $table->decimal('total_price', 14, 2);
            $table->timestamp('delivery_at');
            $table->json('delivery_address');
            $table->text('special_requirements')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable(); // daily, weekdays, weekly
            $table->boolean('is_b2b')->default(true);
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'user_id']);
        });

        Schema::create('corporate_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('company_name');
            $table->string('inn')->nullable()->index();
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('contact_email');
            $table->integer('employees_count')->default(1);
            $table->json('dietary_restrictions')->nullable();
            $table->decimal('monthly_budget', 14, 2)->nullable();
            $table->enum('status', ['active', 'trial', 'suspended', 'churned'])->default('trial');
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_clients');
        Schema::dropIfExists('catering_orders');
        Schema::dropIfExists('catering_menus');
        Schema::dropIfExists('catering_companies');
    }
};
