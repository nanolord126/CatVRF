<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->enum('type', ['cart', 'order', 'b2b_hold', 'manual'])->default('cart');
            $table->enum('status', ['active', 'confirmed', 'released', 'expired'])->default('active');
            $table->timestamp('expires_at')->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason')->nullable();
            $table->string('correlation_id')->index();
            $table->json('metadata')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status', 'expires_at']);
            $table->index(['inventory_id', 'status']);
            $table->index(['cart_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
