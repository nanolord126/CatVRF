<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->index();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('menu_item_id')->nullable()->index();
            
            // Детали позиции
            $table->string('item_name')->comment('Название блюда (для истории)');
            $table->text('item_description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            
            // Статус приготовления
            $table->enum('kitchen_status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])
                  ->default('pending')
                  ->index();
            $table->timestamp('kitchen_started_at')->nullable();
            $table->timestamp('kitchen_ready_at')->nullable();
            
            // Модификаторы и дополнения
            $table->json('modifiers')->nullable()->comment('Модификаторы (без лука, острый и т.д.)');
            $table->json('addons')->nullable()->comment('Дополнительные ингредиенты');
            $table->json('allergens')->nullable()->comment('Аллергены для позиции');
            
            // Примечания
            $table->text('special_instructions')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('restaurant_orders')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('restaurant_menu_items')->onDelete('set null');

            $table->index(['tenant_id', 'order_id']);
            $table->index(['order_id', 'kitchen_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_order_items');
    }
};
