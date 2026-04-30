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
        Schema::create('warehouse_inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('zone_id')->nullable();
            $table->string('product_sku');
            $table->string('product_name');
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->enum('order_type', ['b2b', 'b2c'])->nullable();
            $table->string('branch_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('zone_id')->references('id')->on('warehouse_zones')->onDelete('set null');
            $table->index(['warehouse_id', 'zone_id']);
            $table->index(['warehouse_id', 'product_sku']);
            $table->index(['warehouse_id', 'order_type']);
            $table->index(['product_sku', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_items');
    }
};
