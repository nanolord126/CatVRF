<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('description')->nullable();
            $table->string('unit')->default('pcs'); // pcs, ml, g, etc.
            $table->decimal('stock', 15, 2)->default(0);
            $table->decimal('min_stock', 15, 2)->default(0);
            $table->string('category')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->boolean('is_consumable')->default(false);
            $table->timestamps();
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('type'); // in, out, adjustment
            $table->decimal('quantity', 15, 2);
            $table->string('reason')->nullable();
            $table->string('correlation_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });

        Schema::create('inventory_checks', function (Blueprint $table) {
            $table->id();
            $table->date('check_date');
            $table->string('status')->default('draft'); // draft, completed
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });

        Schema::create('inventory_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_check_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->decimal('expected_quantity', 15, 2);
            $table->decimal('actual_quantity', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_check_items');
        Schema::dropIfExists('inventory_checks');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
    }
};
