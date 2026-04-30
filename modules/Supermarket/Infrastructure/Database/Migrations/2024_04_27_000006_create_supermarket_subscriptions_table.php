<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('status', ['active', 'paused', 'cancelled', 'expired'])->default('active');
            $table->enum('frequency', ['weekly', 'biweekly', 'monthly'])->default('weekly');
            $table->integer('delivery_day')->default(1);
            $table->timestamp('next_delivery_at');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->boolean('is_b2b')->default(false);
            $table->timestamp('pause_until')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'next_delivery_at']);
            $table->index(['buyer_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index('next_delivery_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_subscriptions');
    }
};
