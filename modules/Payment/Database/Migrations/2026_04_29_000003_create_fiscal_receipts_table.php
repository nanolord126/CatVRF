<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_receipts', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('payment_intent_uuid');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->string('type', 20); // prepayment, full_payment, refund
            $table->string('inn', 12); // Seller INN
            $table->string('agent_type', 50); // payment_agent, bank_agent, etc.
            $table->string('agent_name');
            $table->bigInteger('amount_kopecks');
            $table->string('currency', 3)->default('RUB');
            $table->json('items'); // Receipt items with VAT
            $table->string('ofd_provider', 50)->nullable(); // OrangeData, CloudKassir, Atol, etc.
            $table->string('ofd_receipt_id')->nullable();
            $table->string('fiscal_sign')->nullable(); // Фискальный признак
            $table->string('fiscal_document_number')->nullable();
            $table->string('fn_number')->nullable(); // Номер ФН
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('status', 20); // pending, sent, confirmed, failed
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();

            $table->index(['payment_intent_uuid', 'type']);
            $table->index(['tenant_id', 'status']);
            $table->index('order_id');
            $table->index(['status', 'created_at']);
            $table->index('ofd_provider');
            
            // 5-year retention for 54-ФЗ compliance
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_receipts');
    }
};
