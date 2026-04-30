<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Picking Waves Table
 * 
 * Таблица для wave picking (групповой комплектации)
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('picking_waves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('wave_type', ['standard', 'urgent', 'controlled_substances', 'zone_based'])->index();
            $table->enum('status', ['pending', 'in_progress', 'started', 'completed', 'cancelled'])->index()->default('pending');
            $table->integer('order_count')->default(0)->comment('Количество заказов в волне');
            $table->integer('task_count')->default(0)->comment('Количество заданий на комплектацию');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            $table->index(['warehouse_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('wave_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wave_id')->index();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->enum('status', ['pending', 'picking', 'completed'])->default('pending');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wave_id')->references('id')->on('picking_waves')->onDelete('cascade');
        });

        Schema::create('picking_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('wave_id')->index();
            $table->string('group_id')->index()->comment('ID группы заданий');
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('total_quantity')->comment('Общее количество для комплектации');
            $table->integer('order_count')->comment('Количество заказов в задании');
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->integer('estimated_pick_time')->comment('Оценка времени в секундах');
            $table->foreignId('picker_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('picked_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('wave_id')->references('id')->on('picking_waves')->onDelete('cascade');
            $table->index(['wave_id', 'status']);
            $table->index(['location_id', 'status']);
        });

        DB::statement("ALTER TABLE picking_waves COMMENT = 'Wave picking waves'");
        DB::statement("ALTER TABLE wave_orders COMMENT = 'Wave orders'");
        DB::statement("ALTER TABLE picking_tasks COMMENT = 'Picking tasks for waves'");
    }

    public function down(): void
    {
        Schema::dropIfExists('picking_tasks');
        Schema::dropIfExists('wave_orders');
        Schema::dropIfExists('picking_waves');
    }
};
