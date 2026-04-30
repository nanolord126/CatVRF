<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('supermarket_orders')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'refunded'])->default('pending');
            $table->enum('reason_type', ['spoiled', 'wrong_item', 'changed_mind', 'damaged', 'other']);
            $table->text('reason_comment')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->boolean('is_cold_chain')->default(false);
            $table->enum('return_method', ['pickup', 'courier', 'self_delivery'])->default('courier');
            $table->json('images')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('auto_approved')->default(false);
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'created_at']);
            $table->index(['buyer_id', 'created_at']);
            $table->index(['seller_id', 'status']);
            $table->index('is_cold_chain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_returns');
    }
};
