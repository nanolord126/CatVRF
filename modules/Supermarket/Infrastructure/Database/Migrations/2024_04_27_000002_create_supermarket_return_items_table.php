<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('supermarket_returns')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('refund_amount', 10, 2);
            $table->enum('condition', ['good', 'spoiled', 'damaged'])->default('good');
            $table->timestamps();
            
            $table->index(['return_id', 'product_id']);
            $table->index('condition');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_return_items');
    }
};
