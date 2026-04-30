<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_tender_lots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Связь с тендером
            $table->foreignId('tender_id')->constrained('supermarket_tenders')->onDelete('cascade');
            
            // Товар
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_name')->nullable();
            $table->string('product_sku')->nullable();
            
            // Количество и единицы измерения
            $table->decimal('quantity', 15, 3);
            $table->string('unit')->default('pcs')->comment('pcs=шт, kg=кг, ton=тонны, pallet=паллеты');
            $table->json('allowed_units')->nullable()->comment('Разрешенные единицы для предложений');
            
            // Цена
            $table->decimal('starting_price', 15, 2)->nullable()->comment('Стартовая цена (для закупки)');
            $table->decimal('reserve_price', 15, 2)->nullable()->comment('Резервная цена');
            $table->boolean('request_for_price')->default(false)->comment('Запрос цены вместо объявления');
            
            // Характеристики
            $table->json('specifications')->nullable()->comment('Технические характеристики');
            $table->string('brand')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('country_of_origin')->nullable();
            
            // Сроки и условия
            $table->date('expiry_date')->nullable()->comment('Срок годности партии');
            $table->boolean('requires_cold_chain')->default(false);
            
            // Документы
            $table->json('required_documents')->nullable()->comment('Сертификаты, лицензии и т.д.');
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index('tender_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_tender_lots');
    }
};
