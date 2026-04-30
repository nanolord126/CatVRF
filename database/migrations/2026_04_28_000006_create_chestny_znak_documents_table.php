<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Chestny ZNAK Documents Table
 * 
 * Таблица для хранения документов Честный ЗНАК
 * в соответствии с ФЗ-61 (обращение лекарственных средств)
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chestny_znak_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('document_id')->unique()->index()->comment('ID документа в Честный ЗНАК');
            $table->string('document_number')->comment('Номер документа');
            $table->date('document_date');
            $table->enum('type', [
                'LP_INTRODUCE_GOODS',
                'LP_SHIP_GOODS',
                'LP_RETURN_GOODS',
                'LP_WRITE_OFF_GOODS',
            ])->index()->comment('Тип документа');
            $table->enum('product_group', ['pharma', 'tobacco', 'water'])->index();
            $table->enum('status', ['pending', 'processing', 'processed', 'signed', 'rejected'])->default('pending')->index();
            $table->text('status_message')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('signature')->nullable()->comment('ЭЦП документа');
            $table->timestamp('sent_at')->nullable()->comment('Дата отправки в Честный ЗНАК');
            $table->timestamp('processed_at')->nullable()->comment('Дата обработки');
            $table->timestamp('signed_at')->nullable()->comment('Дата подписания');
            $table->timestamp('synced_at')->nullable()->comment('Дата последней синхронизации');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('warehouse_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['warehouse_id', 'status']);
            $table->index(['document_date', 'type']);
            $table->index('created_at');
        });

        DB::statement("ALTER TABLE chestny_znak_documents COMMENT = 'Chestny ZNAK documents per FZ-61'");
    }

    public function down(): void
    {
        Schema::dropIfExists('chestny_znak_documents');
    }
};
