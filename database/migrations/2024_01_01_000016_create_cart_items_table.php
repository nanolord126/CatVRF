<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->integer('product_id');
            $table->integer('quantity');
            $table->integer('price_at_add'); // in kopeks
            $table->integer('current_price'); // in kopeks
            $table->json('metadata')->nullable();
            $table->string('correlation_id', 36)->nullable();
            $table->timestamps();
            
            $table->index(['cart_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
