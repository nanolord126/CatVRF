<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_tender_bids', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Связь с тендером и лотом
            $table->foreignId('tender_id')->constrained('supermarket_tenders')->onDelete('cascade');
            $table->foreignId('tender_lot_id')->constrained('supermarket_tender_lots')->onDelete('cascade');
            
            // Поставщик
            $table->foreignId('supplier_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_tier_id')->nullable()->constrained('supplier_tiers')->onDelete('set null');
            
            // Предложение
            $table->decimal('offered_price', 15, 2);
            $table->decimal('offered_quantity', 15, 3);
            $table->string('unit')->default('pcs');
            
            // Сроки
            $table->date('available_from')->nullable();
            $table->date('delivery_date')->nullable();
            
            // Документы поставщика
            $table->string('guarantee_letter_path')->nullable()->comment('Гарантийное письмо от производителя');
            $table->json('attached_documents')->nullable();
            
            // Статус
            $table->enum('status', ['submitted', 'reviewed', 'accepted', 'rejected', 'withdrawn'])->default('submitted');
            
            // Оценка
            $table->decimal('rating', 3, 2)->nullable()->comment('Оценка предложения');
            $table->text('review_comment')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            $table->index('tender_id');
            $table->index('tender_lot_id');
            $table->index('supplier_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_tender_bids');
    }
};
