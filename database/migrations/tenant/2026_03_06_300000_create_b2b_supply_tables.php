<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: B2B supply tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('name')->index();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_id')->unique()->nullable(); // ИНН
            $table->json('geo_location')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0); // Кредитный лимит B2B
            $table->enum('status', ['ACTIVE', 'SUSPENDED', 'BLOCKED'])->default('ACTIVE');
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 2. Заказы на закупку (Purchase Orders)
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('creator_id')->constrained('users'); // Кто создал (менеджер или AI)
            $table->string('order_number')->unique();
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'SHIPPED', 'DELIVERED', 'CANCELLED'])->default('DRAFT');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['UNPAID', 'PARTIAL', 'PAID'])->default('UNPAID');
            $table->timestamp('expected_delivery_at')->nullable();
            $table->uuid('correlation_id')->nullable()->index();
            $table->timestamps();
        });

        // 3. Состав заказа (Purchase Order Items)
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products'); // Из модуля Inventory
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
