<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('general');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 8, 2)->nullable();
            $table->decimal('longitude', 8, 2)->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_main')->default(false);
            $table->integer('capacity')->default(0);
            $table->integer('capacity_used')->default(0);
            $table->integer('b2b_capacity')->default(0);
            $table->integer('b2b_capacity_used')->default(0);
            $table->integer('b2c_capacity')->default(0);
            $table->integer('b2c_capacity_used')->default(0);
            $table->json('operating_hours')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'is_main']);
        });

        Schema::create('unified_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('unified_warehouses')->onDelete('cascade');
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('b2b_quantity')->default(0);
            $table->integer('b2c_quantity')->default(0);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->integer('min_quantity')->default(0);
            $table->integer('max_quantity')->default(0);
            $table->integer('reorder_point')->default(0);
            $table->string('batch_number')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->string('location')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['warehouse_id', 'sku']);
            $table->index(['tenant_id', 'warehouse_id']);
            $table->index(['warehouse_id', 'quantity']);
        });

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('unified_warehouses')->onDelete('cascade');
            $table->string('order_id');
            $table->string('order_type'); // 'b2b' or 'b2c'
            $table->string('sku');
            $table->integer('quantity');
            $table->string('status')->default('reserved');
            $table->timestamp('reserved_at');
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable();
            $table->uuid('uuid')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'order_id', 'order_type']);
            $table->index(['warehouse_id', 'order_type']);
            $table->index(['order_type', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('unified_stocks');
        Schema::dropIfExists('unified_warehouses');
    }
};
