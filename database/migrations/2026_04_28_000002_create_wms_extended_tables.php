<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('business_group_id')->nullable();
            $table->string('batch_number');
            $table->date('expiry_date');
            $table->integer('quantity')->default(0);
            $table->string('serial_number')->nullable();
            $table->string('status')->default('quarantine');
            $table->timestamp('released_at')->nullable();
            $table->unsignedBigInteger('released_by')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->unsignedBigInteger('held_by')->nullable();
            $table->text('hold_reason')->nullable();
            $table->timestamp('recalled_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->string('correlation_id')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'batch_number']);
            $table->index(['warehouse_id', 'status']);
            $table->index('expiry_date');
            $table->index('tenant_id');
            $table->index('business_group_id');
        });

        Schema::create('batch_recalls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id');
            $table->string('recall_type');
            $table->text('reason');
            $table->string('status')->default('active');
            $table->text('resolution')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('batch_id');
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('serial_number_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('serial_number');
            $table->uuid('batch_id')->nullable();
            $table->string('movement_type');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('tracked_by');
            $table->timestamp('tracked_at');

            $table->index('serial_number');
            $table->index('batch_id');
            $table->index(['reference_type', 'reference_id']);
            $table->index('tenant_id');
        });

        Schema::create('cycle_count_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('count_type');
            $table->string('status')->default('planned');
            $table->date('scheduled_date');
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index(['warehouse_id', 'status']);
            $table->index('tenant_id');
        });

        Schema::create('cycle_count_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('plan_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->integer('expected_quantity');
            $table->integer('actual_quantity')->nullable();
            $table->integer('discrepancy')->nullable();
            $table->decimal('variance_percentage', 5, 2)->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('counted_by')->nullable();
            $table->timestamp('counted_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('plan_id');
            $table->index('inventory_item_id');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedBigInteger('business_group_id')->nullable()->after('tenant_id');
            $table->string('supplier_name')->nullable()->after('name');
            $table->string('performed_by')->nullable()->after('supplier_name');
            $table->string('approved_by')->nullable()->after('performed_by');
            $table->decimal('unit_cost', 10, 2)->nullable()->after('max_stock_threshold');
            $table->string('abc_class')->nullable()->after('unit_cost');
            $table->boolean('requires_marking')->default(false)->after('abc_class');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['supplier_name', 'performed_by', 'approved_by', 'unit_cost', 'abc_class', 'requires_marking']);
        });

        Schema::dropIfExists('cycle_count_items');
        Schema::dropIfExists('cycle_count_plans');
        Schema::dropIfExists('serial_number_tracking');
        Schema::dropIfExists('batch_recalls');
        Schema::dropIfExists('inventory_batches');
    }
};
