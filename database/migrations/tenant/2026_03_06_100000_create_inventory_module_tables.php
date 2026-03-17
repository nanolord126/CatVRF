<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->comment('Инвентарь: товары, SKU, остатки.');
                $table->id();
                $table->string('sku')->unique()->comment('Артикул товара');
                $table->string('name')->comment('Название товара');
                $table->decimal('price', 15, 2)->comment('Цена');
                $table->integer('quantity')->default(0)->comment('Остаток');
                $table->timestamps();
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->index(['sku', 'quantity']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
