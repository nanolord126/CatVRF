<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_supplier_penalties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Поставщик
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            
            // Пострадавший бизнес (кому поставили просрочку)
            $table->foreignId('affected_business_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Связь с поставкой/документом
            $table->foreignId('document_id')->nullable()->constrained('supermarket_documents')->onDelete('set null');
            $table->foreignId('supply_chain_link_id')->nullable()->constrained('supply_chain_links')->onDelete('set null');
            
            // Сумма поставки
            $table->decimal('supply_amount', 15, 2);
            
            // Штраф (3x от суммы поставки)
            $table->decimal('penalty_amount', 15, 2);
            
            // Распределение штрафа
            $table->decimal('platform_share', 15, 2)->comment('2x от суммы поставки - платформе');
            $table->decimal('business_share', 15, 2)->comment('1x от суммы поставки - бизнесу');
            
            // Статус штрафа
            $table->enum('status', ['pending', 'charged', 'paid', 'disputed', 'waived'])->default('pending');
            
            // Детали
            $table->text('reason')->nullable();
            $table->json('expired_products')->nullable()->comment('Список просроченных товаров');
            $table->integer('expired_quantity')->default(0);
            
            // Даты
            $table->timestamp('charged_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index('supplier_id');
            $table->index('affected_business_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_supplier_penalties');
    }
};
