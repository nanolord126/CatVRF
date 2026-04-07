<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grocery_stores')) {
            return;
        }

        Schema::create('grocery_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('store_type')->comment('magnit, pyaterochka, vkusvelle, farm_market');
            $table->string('address');
            $table->json('geo_point')->nullable();
            $table->json('schedule')->nullable();
            $table->boolean('is_verified')->default(false)->index();
            $table->integer('commission_rate')->default(1400)->comment('14%');
            $table->integer('average_delivery_time')->nullable()->comment('in minutes');
            $table->boolean('supports_fast_delivery')->default(true)->comment('15-60 min slots');
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'uuid']);
            $table->index(['tenant_id', 'supports_fast_delivery']);
            $table->comment('Grocery stores (Magnit, Pyaterochka, farm markets)');
        });

        Schema::create('grocery_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('grocery_stores')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('category')->comment('vegetables, dairy, meat, bakery');
            $table->string('sku')->nullable();
            $table->integer('current_stock')->default(0);
            $table->integer('hold_stock')->default(0);
            $table->integer('price')->comment('in kopeks');
            $table->string('unit')->comment('kg, l, piece, etc.');
            $table->boolean('is_available')->default(true)->index();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'category', 'is_available']);
            $table->comment('Products in grocery stores');
        });

        Schema::create('grocery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('store_id')->constrained('grocery_stores')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->enum('delivery_type', ['fast_slot', 'scheduled', 'pickup'])->default('fast_slot')->index();
            $table->integer('total_price')->comment('in kopeks');
            $table->integer('delivery_fee')->nullable();
            $table->enum('status', ['pending', 'picked', 'in_delivery', 'delivered', 'cancelled'])->default('pending')->index();
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('pending')->index();
            $table->string('delivery_address')->nullable();
            $table->geometry('delivery_point')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->dateTime('hold_until')->nullable()->comment('20-min hold');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['store_id', 'created_at']);
            $table->comment('Grocery orders with fast delivery support');
        });

        Schema::create('grocery_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('grocery_orders')->onDelete('cascade')->index();
            $table->foreignId('product_id')->constrained('grocery_products')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('quantity');
            $table->string('unit');
            $table->integer('unit_price')->comment('in kopeks at order time');
            $table->timestamps();

            $table->comment('Items in grocery order');
        });

        Schema::create('delivery_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('grocery_stores')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->date('slot_date')->index();
            $table->time('slot_start');
            $table->time('slot_end');
            $table->integer('max_capacity')->comment('max orders in slot');
            $table->integer('current_orders')->default(0);
            $table->integer('delivery_fee')->comment('in kopeks');
            $table->boolean('is_available')->default(true)->index();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'slot_date', 'slot_start', 'slot_end']);
            $table->index(['store_id', 'slot_date', 'is_available']);
            $table->comment('Fast delivery slots (15-60 min windows)');
        });

        Schema::create('slot_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('grocery_orders')->onDelete('cascade')->index();
            $table->foreignId('slot_id')->constrained('delivery_slots')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->dateTime('booked_at');
            $table->enum('status', ['reserved', 'confirmed', 'completed', 'cancelled'])->default('reserved')->index();
            $table->timestamps();

            $table->unique(['order_id', 'slot_id']);
            $table->comment('Assignment of orders to delivery slots');
        });

        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('grocery_stores')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->float('rating', 3, 2)->default(0);
            $table->integer('deliveries_count')->default(0);
            $table->enum('status', ['available', 'busy', 'offline'])->default('offline')->index();
            $table->geometry('current_location')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->comment('Delivery partners for fast orders');
        });

        Schema::create('delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('grocery_orders')->onDelete('cascade')->index();
            $table->foreignId('partner_id')->nullable()->constrained('delivery_partners')->onDelete('set null')->index();
            $table->uuid('uuid')->unique()->index();
            $table->enum('status', ['assigned', 'picked_up', 'in_transit', 'delivered', 'failed'])->default('assigned')->index();
            $table->dateTime('event_time');
            $table->geometry('location')->nullable();
            $table->integer('eta_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['order_id', 'event_time']);
            $table->comment('Delivery tracking logs');
        });

        Schema::create('grocery_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade')->index();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->index();
            $table->foreignId('store_id')->constrained('grocery_stores')->onDelete('cascade')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('rating')->comment('1-5');
            $table->text('comment');
            $table->json('photos')->nullable();
            $table->integer('freshness')->nullable();
            $table->integer('delivery_speed')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->comment('Grocery store reviews');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_reviews');
        Schema::dropIfExists('delivery_logs');
        Schema::dropIfExists('delivery_partners');
        Schema::dropIfExists('slot_bookings');
        Schema::dropIfExists('delivery_slots');
        Schema::dropIfExists('grocery_order_items');
        Schema::dropIfExists('grocery_orders');
        Schema::dropIfExists('grocery_products');
        Schema::dropIfExists('grocery_stores');
    }
};


