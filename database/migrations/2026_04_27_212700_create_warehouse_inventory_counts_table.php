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
        Schema::create('warehouse_inventory_counts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('zone_id')->nullable();
            $table->string('count_number')->unique();
            $table->enum('count_type', ['full', 'partial', 'cycle', 'spot']);
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'approved', 'cancelled'])->default('scheduled');
            $table->datetime('scheduled_date');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->integer('total_items_expected')->nullable();
            $table->integer('total_items_counted')->nullable();
            $table->integer('discrepancies_found')->nullable();
            $table->string('performed_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('zone_id')->references('id')->on('warehouse_zones')->onDelete('set null');
            $table->index(['warehouse_id', 'status']);
            $table->index(['warehouse_id', 'scheduled_date']);
            $table->index(['status', 'scheduled_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_inventory_counts');
    }
};
