<?php

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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->enum('category', ['medication', 'feed', 'grooming_product', 'kitchen_product', 'consumable', 'other'])->default('other');
            $table->string('batch_number')->nullable();
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->integer('shelf_life_days')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->string('unit')->default('шт');
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->integer('min_stock_level')->nullable();
            $table->string('storage_conditions')->nullable();
            $table->string('storage_location')->nullable();
            $table->boolean('is_controlled')->default(false);
            $table->enum('status', ['active', 'expiring_soon', 'expired', 'quarantine'])->default('active');
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'sku']);
            $table->index(['tenant_id', 'expiry_date']);
            $table->index(['tenant_id', 'category', 'status']);
            $table->index(['tenant_id', 'is_controlled', 'expiry_date']);
            $table->index('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
