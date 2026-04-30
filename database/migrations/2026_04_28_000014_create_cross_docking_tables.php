<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Cross Docking Tables
 * 
 * Таблицы для cross-docking оптимизации
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cross_docking_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('incoming_shipment_id')->constrained('incoming_shipments')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->index()->default('pending');
            $table->integer('item_count')->default(0)->comment('Количество товаров для cross-docking');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->index(['warehouse_id', 'status']);
            $table->index(['incoming_shipment_id']);
        });

        Schema::create('cross_docking_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->index();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->comment('Количество');
            $table->uuid('batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
            $table->enum('status', ['pending', 'processed', 'cancelled'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('task_id')->references('id')->on('cross_docking_tasks')->onDelete('cascade');
        });

        DB::statement("ALTER TABLE cross_docking_tasks COMMENT = 'Cross-docking tasks'");
        DB::statement("ALTER TABLE cross_docking_items COMMENT = 'Cross-docking task items'");
    }

    public function down(): void
    {
        Schema::dropIfExists('cross_docking_items');
        Schema::dropIfExists('cross_docking_tasks');
    }
};
