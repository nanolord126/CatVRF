<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grocery_products')) {
            return;
        }

        Schema::create('grocery_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('store_id')->constrained('grocery_stores')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('grocery_categories');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price');
            $table->integer('stock_quantity');
            $table->string('unit')->default('pcs');
            $table->string('image_url')->nullable();
            $table->boolean('is_available')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'store_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grocery_products');
    }
};
