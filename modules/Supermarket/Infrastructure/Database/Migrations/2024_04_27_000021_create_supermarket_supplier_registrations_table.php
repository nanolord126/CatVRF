<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supermarket_supplier_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Поставщик
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_tier_id')->constrained('supplier_tiers')->onDelete('restrict');
            
            // CRM интеграция
            $table->foreignId('crm_contact_id')->nullable()->constrained()->onDelete('set null');
            
            // Тип регистрации
            $table->enum('registration_type', ['b2b_only', 'b2c_only', 'both'])->default('b2b_only');
            
            // Юридические данные
            $table->string('company_name');
            $table->string('inn', 12)->unique();
            $table->string('kpp', 9)->nullable();
            $table->string('ogrn', 15)->nullable();
            $table->string('legal_address');
            $table->string('actual_address')->nullable();
            
            // Банковские реквизиты
            $table->string('bank_name')->nullable();
            $table->string('bik', 9)->nullable();
            $table->string('account_number', 20)->nullable();
            $table->string('correspondent_account', 20)->nullable();
            
            // Контакты
            $table->string('contact_person');
            $table->string('contact_phone');
            $table->string('contact_email');
            
            // Документы
            $table->string('guarantee_letter_path')->nullable()->comment('Гарантийное письмо от производителя');
            $table->json('attached_documents')->nullable()->comment('Сертификаты, лицензии и т.д.');
            
            // Склады
            $table->json('warehouse_ids')->nullable()->comment('ID складов для B2B/B2C');
            
            // Статус
            $table->enum('status', ['pending', 'under_review', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id');
            $table->index('supplier_tier_id');
            $table->index('status');
            $table->index('inn');
            $table->index('registration_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supermarket_supplier_registrations');
    }
};
