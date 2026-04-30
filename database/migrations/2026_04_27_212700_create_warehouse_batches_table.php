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
        Schema::create('warehouse_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('product_sku');
            $table->string('batch_number');
            $table->string('lot_number');
            $table->date('manufacture_date');
            $table->date('expiry_date');
            $table->integer('initial_quantity')->default(0);
            $table->integer('current_quantity')->default(0);
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->uuid('warehouse_id');
            $table->uuid('zone_id')->nullable();
            $table->uuid('bin_id')->nullable();
            $table->string('supplier_id')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('certificate_number')->nullable();
            $table->enum('status', ['active', 'expiring_soon', 'expired', 'depleted', 'quarantine'])->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('warehouse_products')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('zone_id')->references('id')->on('warehouse_zones')->onDelete('set null');
            $table->foreign('bin_id')->references('id')->on('warehouse_bins')->onDelete('set null');
            $table->index(['product_id', 'expiry_date', 'current_quantity', 'status']);
            $table->index(['warehouse_id', 'expiry_date']);
            $table->index(['warehouse_id', 'status', 'expiry_date']);
            $table->unique(['product_id', 'batch_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_batches');
    }
};
