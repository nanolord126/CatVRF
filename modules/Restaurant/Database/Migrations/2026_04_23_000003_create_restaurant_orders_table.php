<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique()->comment('Номер заказа для чека');
            
            // Тип и статус
            $table->enum('type', ['dine_in', 'delivery', 'pickup', 'pre_order'])->default('dine_in')->index();
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'served', 'delivering', 'delivered', 'picked_up', 'cancelled', 'refunded', 'completed'])
                  ->default('pending')
                  ->index();
            
            // Связи
            $table->unsignedBigInteger('table_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index()->comment('Пользователь (если зарегистрирован)');
            $table->unsignedBigInteger('guest_id')->nullable()->index()->comment('Гость (если не зарегистрирован)');
            $table->unsignedBigInteger('waiter_id')->nullable()->index()->comment('Официант');
            $table->unsignedBigInteger('courier_id')->nullable()->index()->comment('Курьер (для доставки)');
            $table->unsignedBigInteger('crm_deal_id')->nullable()->index()->comment('Связь с CRM сделкой');
            
            // Детали заказа
            $table->integer('items_count')->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('RUB');
            
            // Время
            $table->timestamp('order_time')->nullable()->comment('Время заказа');
            $table->timestamp('estimated_ready_time')->nullable()->comment('Ожидаемое время готовности');
            $table->timestamp('actual_ready_time')->nullable()->comment('Фактическое время готовности');
            $table->timestamp('served_time')->nullable()->comment('Время подачи/доставки');
            $table->timestamp('pickup_time')->nullable()->comment('Время самовывоза');
            
            // Доставка
            $table->string('delivery_address')->nullable();
            $table->string('delivery_phone')->nullable();
            $table->string('delivery_name')->nullable();
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_lon', 11, 8)->nullable();
            $table->text('delivery_instructions')->nullable();
            
            // Оплата
            $table->enum('payment_status', ['pending', 'processing', 'paid', 'refunded', 'failed'])->default('pending')->index();
            $table->enum('payment_method', ['cash', 'card', 'online', 'split'])->nullable();
            $table->string('payment_transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Дополнительно
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('table_id')->references('id')->on('restaurant_tables')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('crm_deal_id')->references('id')->on('crm_deals')->onDelete('set null');

            $table->index(['tenant_id', 'restaurant_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['restaurant_id', 'status']);
            $table->index(['tenant_id', 'order_time']);
            $table->index(['restaurant_id', 'order_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_orders');
    }
};
