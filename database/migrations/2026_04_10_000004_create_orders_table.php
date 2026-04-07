<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Центральная таблица заказов маркетплейса.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 *
 * Единая точка входа для всех вертикальных заказов.
 * Доменные заказы (book_orders, beauty_orders и т.д.) ссылаются на эту таблицу.
 * Все суммы в КОПЕЙКАХ (integer).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');

            // Вертикаль (beauty, food, furniture, fashion, medical и т.д.)
            $table->string('vertical', 50)->index();

            // Статус заказа
            $table->string('status', 30)->default('pending')->index();
            // pending | confirmed | processing | shipped | delivered | cancelled | refunded

            // Финансы (все суммы в копейках)
            $table->unsignedBigInteger('subtotal')->default(0);          // сумма товаров
            $table->unsignedBigInteger('shipping_cost')->default(0);     // стоимость доставки
            $table->unsignedBigInteger('discount_amount')->default(0);   // скидка
            $table->unsignedBigInteger('total')->default(0);             // итог = subtotal + shipping - discount
            $table->unsignedBigInteger('platform_commission')->default(0); // комиссия платформы
            $table->unsignedBigInteger('seller_earnings')->default(0);   // заработок продавца
            $table->string('currency', 3)->default('RUB');

            // Оплата
            $table->string('payment_status', 30)->default('pending');
            // pending | paid | failed | refunded | partial_refund
            $table->string('payment_method', 30)->nullable();            // card | sbp | wallet | b2b_credit
            $table->string('payment_id')->nullable();                    // ID платежа в шлюзе
            $table->timestamp('paid_at')->nullable();

            // B2B
            $table->boolean('is_b2b')->default(false);
            $table->string('inn', 12)->nullable();                       // ИНН для B2B
            $table->string('business_card_id')->nullable();

            // Адрес доставки (snapshot)
            $table->string('delivery_address')->nullable();
            $table->decimal('delivery_lat', 10, 8)->nullable();
            $table->decimal('delivery_lon', 11, 8)->nullable();
            $table->string('tracking_number')->nullable();

            // Возврат
            $table->string('refund_status', 30)->default('none');        // none | requested | processing | completed
            $table->unsignedBigInteger('refund_amount')->default(0);
            $table->timestamp('refunded_at')->nullable();

            // Метаданные
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['user_id', 'status']);
            $table->index(['vertical', 'status']);
            $table->index(['is_b2b', 'tenant_id']);
            $table->comment('Central marketplace orders table — all verticals');
        });

        // Таблица позиций заказа
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->string('product_type', 100)->nullable(); // Polymorphic: BeautyProduct, Dish, FurnitureItem…
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name')->nullable();        // snapshot
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_price')->default(0);  // цена за единицу в копейках
            $table->unsignedBigInteger('total_price')->default(0); // quantity * unit_price в копейках
            $table->json('options')->nullable();                    // цвет, размер, модификации
            $table->string('correlation_id', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['order_id']);
            $table->index(['product_type', 'product_id']);
            $table->comment('Order line items — individual products per order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
