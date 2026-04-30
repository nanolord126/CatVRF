<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('business_group_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('loyalty_program_id')->index();
            $table->unsignedBigInteger('guest_id')->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            
            // Тип транзакции
            $table->enum('type', ['earned', 'redeemed', 'bonus', 'expired', 'adjusted'])
                  ->index();
            
            // Сумма бонуса
            $table->decimal('amount_change', 10, 2)->comment('Изменение баланса бонусов (+/-)');
            $table->decimal('balance_before', 10, 2)->default(0)->comment('Баланс до транзакции');
            $table->decimal('balance_after', 10, 2)->default(0)->comment('Баланс после транзакции');
            
            // Детали заказа
            $table->decimal('order_amount', 10, 2)->nullable()->comment('Сумма заказа');
            $table->decimal('bonus_percentage', 5, 4)->nullable()->comment('% бонуса');
            
            // Описание
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            
            // Истечение
            $table->timestamp('expires_at')->nullable()->comment('Дата истечения баллов');
            $table->boolean('is_expired')->default(false);
            
            $table->json('metadata')->nullable();
            $table->string('correlation_id')->nullable()->index();
            
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
            $table->foreign('loyalty_program_id')->references('id')->on('restaurant_loyalty_programs')->onDelete('cascade');
            $table->foreign('guest_id')->references('id')->on('restaurant_guests')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('restaurant_orders')->onDelete('set null');

            $table->index(['tenant_id', 'guest_id']);
            $table->index(['guest_id', 'created_at']);
            $table->index(['loyalty_program_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_loyalty_transactions');
    }
};
