<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('uuid')->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->enum('status', ['active', 'ordered', 'abandoned', 'expired'])->default('active');
            $table->timestamp('reserved_until')->nullable(); // резерв 20 минут
            $table->json('tags')->nullable();
            $table->timestamps();

            // 1 продавец = 1 активная корзина на пользователя
            $table->unique(['tenant_id', 'user_id', 'seller_id', 'status'], 'unique_active_cart_per_seller');
            $table->index(['user_id', 'status']);
            $table->index(['reserved_until']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('uuid')->unique();
            $table->integer('quantity')->default(1);
            $table->unsignedBigInteger('price_at_add');     // цена в момент добавления (копейки)
            $table->unsignedBigInteger('current_price');    // актуальная цена (копейки)
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
