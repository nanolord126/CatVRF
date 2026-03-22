<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grocery_orders')) {
            return;
        }

        Schema::create('grocery_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('store_id')->constrained('grocery_stores');
            $table->foreignId('user_id')->constrained();
            $table->integer('total_price');
            $table->string('status')->default('pending');
            $table->text('delivery_address');
            $table->timestamp('delivery_slot')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'store_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_orders');
    }
};
