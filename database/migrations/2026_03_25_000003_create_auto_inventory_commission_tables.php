<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Taxi drivers table - skip if already exists
        if (!Schema::hasTable('taxi_drivers')) {
            Schema::create('taxi_drivers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('license_number')->unique();
                $table->float('rating')->default(0);
                $table->integer('review_count')->default(0);
                $table->string('current_location', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['tenant_id', 'is_active']);
                $table->comment('Taxi drivers');
            });
        }

        // Taxi vehicles table - skip if already exists
        if (!Schema::hasTable('taxi_vehicles')) {
            Schema::create('taxi_vehicles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('driver_id')->nullable()->constrained('taxi_drivers')->onDelete('cascade');
                $table->foreignId('fleet_id')->nullable();
                $table->string('brand');
                $table->string('model');
                $table->string('license_plate')->unique();
                $table->enum('class', ['economy', 'comfort', 'business', 'premium']);
                $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
                $table->timestamps();
                $table->index(['driver_id']);
                $table->comment('Taxi vehicles');
            });
        }

        // Taxi rides table - skip if already exists
        if (!Schema::hasTable('taxi_rides')) {
            Schema::create('taxi_rides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('passenger_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('driver_id')->nullable()->constrained('taxi_drivers')->onDelete('cascade');
                $table->foreignId('vehicle_id')->nullable()->constrained('taxi_vehicles')->onDelete('cascade');
                $table->string('pickup_point', 255);
                $table->string('dropoff_point', 255);
                $table->enum('status', ['requested', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('requested');
                $table->bigInteger('estimated_price')->comment('Estimated price in kopeks');
                $table->bigInteger('final_price')->nullable()->comment('Final price in kopeks');
                $table->float('surge_multiplier')->default(1.0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('correlation_id')->index();
                $table->timestamps();
                $table->index(['passenger_id', 'status']);
                $table->comment('Taxi rides');
            });
        }

        // Taxi surge zones table - skip if already exists
        if (!Schema::hasTable('taxi_surge_zones')) {
            Schema::create('taxi_surge_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('zone_area');
                $table->float('surge_multiplier')->default(1.0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['tenant_id', 'is_active']);
                $table->comment('Taxi surge pricing zones');
            });
        }

        // Auto parts table - skip if already exists
        if (!Schema::hasTable('auto_parts')) {
            Schema::create('auto_parts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('sku')->unique();
                $table->string('name');
                $table->string('brand');
                $table->integer('current_stock')->default(0);
                $table->bigInteger('price')->comment('Price in kopeks');
                $table->timestamps();
                $table->index(['tenant_id', 'sku']);
                $table->comment('Auto parts');
            });
        }

        // Auto services table - skip if already exists
        if (!Schema::hasTable('auto_services')) {
            Schema::create('auto_services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->bigInteger('price')->comment('Price in kopeks');
                $table->integer('duration_minutes')->default(30);
                $table->timestamps();
                $table->index(['tenant_id']);
                $table->comment('Auto service offerings');
            });
        }

        // Auto repair orders table - skip if already exists
        if (!Schema::hasTable('auto_repair_orders')) {
            Schema::create('auto_repair_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('service_id')->constrained('auto_services')->onDelete('cascade');
                $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
                $table->bigInteger('total_price')->comment('Total in kopeks');
                $table->dateTime('appointment_datetime');
                $table->string('correlation_id')->index();
                $table->timestamps();
                $table->index(['user_id', 'status']);
                $table->comment('Auto repair orders');
            });
        }

        // Commission rules table - skip if already exists
        if (!Schema::hasTable('commission_rules')) {
            Schema::create('commission_rules', function (Blueprint $table) {
                $table->id();
                $table->string('vertical')->index();
                $table->float('base_rate')->comment('Commission rate %');
                $table->bigInteger('min_turnover')->default(0)->comment('Min turnover in kopeks');
                $table->timestamps();
                $table->comment('Per-vertical commission rates');
            });
        }

        // Commission records table - skip if already exists
        if (!Schema::hasTable('commission_records')) {
            Schema::create('commission_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('cascade');
                $table->string('vertical')->index();
                $table->bigInteger('amount')->comment('Commission in kopeks');
                $table->float('rate')->comment('Applied rate %');
                $table->foreignId('source_transaction_id')->nullable();
                $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->string('correlation_id')->index();
                $table->timestamps();
                $table->index(['tenant_id', 'status']);
                $table->comment('Commission records');
            });
        }

        // Inventory items table - skip if already exists
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('cascade');
                $table->foreignId('product_id')->nullable();
                $table->integer('current_stock')->default(0);
                $table->integer('hold_stock')->default(0);
                $table->integer('min_stock_threshold')->default(10);
                $table->integer('max_stock_threshold')->default(100);
                $table->timestamp('last_checked_at')->nullable();
                $table->string('correlation_id')->index();
                $table->json('tags')->nullable();
                $table->timestamps();
                $table->index(['tenant_id', 'business_group_id']);
                $table->comment('Inventory management');
            });
        }

        // Stock movements table - skip if already exists
        if (!Schema::hasTable('stock_movements')) {
            Schema::create('stock_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
                $table->enum('type', ['in', 'out', 'adjust', 'reserve', 'release', 'correction']);
                $table->integer('quantity')->comment('Signed integer');
                $table->text('reason')->nullable();
                $table->string('source_type')->nullable();
                $table->foreignId('source_id')->nullable();
                $table->string('correlation_id')->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
                $table->timestamps();
                $table->index(['inventory_item_id', 'type']);
                $table->comment('Stock movement history');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('commission_records');
        Schema::dropIfExists('commission_rules');
        Schema::dropIfExists('auto_repair_orders');
        Schema::dropIfExists('auto_services');
        Schema::dropIfExists('auto_parts');
        Schema::dropIfExists('taxi_surge_zones');
        Schema::dropIfExists('taxi_rides');
        Schema::dropIfExists('taxi_vehicles');
        Schema::dropIfExists('taxi_drivers');
    }
};


