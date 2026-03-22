<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grocery_stores')) return;

        Schema::create('grocery_stores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('address');
            $table->point('geo_point')->nullable();
            $table->enum('store_type', ['supermarket', 'cafe', 'butcher', 'greengrocer']);
            $table->json('cuisines')->nullable();
            $table->json('delivery_zones')->nullable();
            $table->boolean('is_active')->default(true);
            $table->float('rating')->default(0);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Магазины и кафе с доставкой');
        });

        Schema::create('grocery_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('icon')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('grocery_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('unit');
            $table->boolean('is_available')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('grocery_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('inn')->nullable();
            $table->unsignedBigInteger('business_card_id')->nullable();
            $table->uuid('uuid')->unique()->index();
            $table->json('items');
            $table->decimal('total_price', 10, 2);
            $table->string('delivery_address');
            $table->string('delivery_slot')->nullable();
            $table->enum('status', ['pending', 'preparing', 'delivering', 'delivered', 'cancelled']);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_orders');
        Schema::dropIfExists('grocery_products');
        Schema::dropIfExists('grocery_categories');
        Schema::dropIfExists('grocery_stores');
    }
};
