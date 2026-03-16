<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: B2B marketplace tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('name');
            $table->string('brand_name')->nullable();
            $table->string('registration_number')->unique(); // ИНН
            $table->string('contact_email')->unique();
            $table->string('contact_phone');
            $table->text('legal_address');
            $table->string('category'); // Food, Medical, Construction
            $table->decimal('ai_trust_score', 3, 2)->default(5.00);
            $table->json('geo_coverage')->nullable(); // Regions or cities
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Wholesale Products
        Schema::create('b2b_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('b2b_manufacturers')->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit'); // kg, box, pallet, unit
            $table->decimal('base_wholesale_price', 15, 2);
            $table->integer('min_order_quantity')->default(1);
            $table->integer('stock_quantity')->default(0);
            $table->json('specifications')->nullable(); // Technical data
            $table->string('tags')->nullable()->index(); // Tags for AI matching
            $table->string('correlation_id')->index();
            $table->timestamps();
            $table->index(['manufacturer_id', 'sku']);
        });

        // 3. Wholesale Contracts (Manufacturer <-> Tenant)
        Schema::create('b2b_wholesale_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('b2b_manufacturers');
            $table->string('tenant_id')->index(); // UUID of the purchasing tenant
            $table->string('contract_number')->unique();
            $table->date('signed_at');
            $table->date('expires_at')->nullable();
            $table->decimal('special_discount_percent', 5, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->integer('deferred_payment_days')->default(0);
            $table->enum('status', ['draft', 'active', 'expired', 'terminated'])->default('active');
            $table->string('correlation_id')->index();
            $table->timestamps();
        });

        // 4. B2B Bulk Orders
        Schema::create('b2b_bulk_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manufacturer_id')->constrained('b2b_manufacturers');
            $table->string('tenant_id')->index();
            $table->foreignId('contract_id')->nullable()->constrained('b2b_wholesale_contracts');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2); // Platform fee
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'deferred'])->default('unpaid');
            $table->timestamp('expected_delivery_at')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamps();
        });

        // 5. B2B Order Items
        Schema::create('b2b_bulk_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('b2b_bulk_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('b2b_products');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->string('correlation_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('b2b_bulk_order_items');
        Schema::dropIfExists('b2b_bulk_orders');
        Schema::dropIfExists('b2b_wholesale_contracts');
        Schema::dropIfExists('b2b_products');
        Schema::dropIfExists('b2b_manufacturers');
    }
};
