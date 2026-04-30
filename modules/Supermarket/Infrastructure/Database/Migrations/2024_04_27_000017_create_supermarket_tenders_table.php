<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_tenders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Создатель тендера (через CRM)
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('crm_lead_id')->nullable()->constrained()->onDelete('set null')->comment('ID лида в CatCRM');
            
            // Тип тендера
            $table->enum('type', ['procurement', 'sale'])->default('procurement')->comment('procurement=закупка, sale=продажа партии');
            
            // Статус
            $table->enum('status', ['draft', 'published', 'active', 'closed', 'awarded', 'cancelled'])->default('draft');
            
            // Основная информация
            $table->string('title');
            $table->text('description')->nullable();
            
            // Период
            $table->timestamp('published_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            
            // Бюджет/сумма
            $table->decimal('estimated_budget', 15, 2)->nullable();
            $table->string('currency', 3)->default('RUB');
            
            // Условия
            $table->json('delivery_terms')->nullable();
            $table->json('payment_terms')->nullable();
            
            // Требования к поставщику
            $table->boolean('requires_guarantee_letter')->default(true)->comment('Требуется гарантийное письмо');
            $table->string('guarantee_letter_path')->nullable();
            
            // Ограничения
            $table->json('allowed_supplier_tiers')->nullable()->comment('Разрешенные уровни поставщиков');
            $table->json('restricted_regions')->nullable();
            
            // Результат
            $table->foreignId('awarded_supplier_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('awarded_at')->nullable();
            $table->decimal('final_amount', 15, 2)->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('creator_id');
            $table->index('status');
            $table->index('type');
            $table->index('starts_at');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_tenders');
    }
};
