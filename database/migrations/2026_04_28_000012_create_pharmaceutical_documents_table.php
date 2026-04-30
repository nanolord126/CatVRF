<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Pharmaceutical Documents Table
 * 
 * Таблица для документооборота по движению лекарственных средств
 * в соответствии с ФЗ-61
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmaceutical_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('document_type', ['receipt', 'shipment', 'return', 'write_off', 'transfer', 'adjustment'])->index();
            $table->string('document_number')->unique()->index()->comment('Номер документа');
            $table->timestamp('document_date')->index()->comment('Дата документа');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('counterparty_id')->nullable()->index()->comment('ID контрагента');
            $table->enum('status', ['draft', 'confirmed', 'cancelled', 'archived'])->index()->default('draft');
            $table->integer('total_quantity')->default(0)->comment('Общее количество');
            $table->decimal('total_amount', 15, 2)->default(0)->comment('Общая сумма');
            $table->text('notes')->nullable()->comment('Примечания');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('confirmed_at')->nullable()->comment('Дата подтверждения');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('cancelled_at')->nullable()->comment('Дата отмены');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancellation_reason')->nullable()->comment('Причина отмены');
            $table->timestamp('archived_at')->nullable()->index()->comment('Дата архивации');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // Indexes
            $table->index(['warehouse_id', 'document_date']);
            $table->index(['tenant_id', 'document_type']);
            $table->index(['status', 'document_date']);
        });

        Schema::create('pharmaceutical_document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('document_id')->index();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->uuid('batch_id')->nullable()->constrained('inventory_batches')->onDelete('set null');
            $table->integer('quantity')->comment('Количество');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Цена за единицу');
            $table->decimal('amount', 15, 2)->default(0)->comment('Сумма');
            $table->string('serial_number')->nullable()->index()->comment('Серийный номер');
            $table->string('marking_code')->nullable()->index()->comment('Код маркировки DataMatrix');
            $table->date('expiry_date')->nullable()->comment('Срок годности');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->foreign('document_id')->references('id')->on('pharmaceutical_documents')->onDelete('cascade');
            $table->index(['document_id', 'product_id']);
            $table->index(['batch_id']);
        });

        DB::statement("ALTER TABLE pharmaceutical_documents COMMENT = 'Pharmaceutical movement documents per FZ-61'");
        DB::statement("ALTER TABLE pharmaceutical_document_items COMMENT = 'Pharmaceutical document items'");
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmaceutical_document_items');
        Schema::dropIfExists('pharmaceutical_documents');
    }
};
