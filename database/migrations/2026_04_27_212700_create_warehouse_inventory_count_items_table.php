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
        Schema::create('warehouse_inventory_count_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_count_id');
            $table->string('product_sku');
            $table->string('product_name');
            $table->integer('expected_quantity')->default(0);
            $table->integer('counted_quantity')->default(0);
            $table->integer('discrepancy')->default(0);
            $table->string('bin_code')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inventory_count_id')->references('id')->on('warehouse_inventory_counts')->onDelete('cascade');
            $table->index(['inventory_count_id', 'product_sku']);
            $table->index(['inventory_count_id', 'discrepancy']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_count_items');
    }
};
