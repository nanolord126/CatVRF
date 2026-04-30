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
        Schema::create('warehouse_stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('from_zone_id')->nullable();
            $table->uuid('to_zone_id')->nullable();
            $table->uuid('inventory_item_id');
            $table->string('product_sku');
            $table->integer('quantity');
            $table->enum('movement_type', ['receipt', 'transfer', 'picking', 'packing', 'shipment', 'return', 'adjustment', 'damage', 'loss', 'conversion']);
            $table->enum('order_type', ['b2b', 'b2c'])->nullable();
            $table->string('order_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('from_zone_id')->references('id')->on('warehouse_zones')->onDelete('set null');
            $table->foreign('to_zone_id')->references('id')->on('warehouse_zones')->onDelete('set null');
            $table->foreign('inventory_item_id')->references('id')->on('warehouse_inventory_items')->onDelete('cascade');
            $table->index(['warehouse_id', 'created_at']);
            $table->index(['inventory_item_id', 'created_at']);
            $table->index(['product_sku', 'created_at']);
            $table->index(['movement_type', 'created_at']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stock_movements');
    }
};
