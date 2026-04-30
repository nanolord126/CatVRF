<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Warehouse Documents Table
 * 
 * Таблица для хранения документов в системе документооборота склада:
 * - Акты приемки
 * - Накладные
 * - Акты списания
 * - Акты инвентаризации
 * - Прочие документы
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id')->index();
            $table->string('document_number')->unique();
            $table->enum('document_type', [
                'receipt_act',
                'shipment_act',
                'transfer_act',
                'write_off_act',
                'inventory_act',
                'return_act',
                'damage_act',
                'loss_act',
                'quarantine_act',
                'expiration_act',
                'adjustment_act',
                'supplier_invoice',
                'customs_declaration',
                'certificate',
            ])->index();
            $table->enum('status', [
                'draft',
                'pending_approval',
                'approved',
                'rejected',
                'archived',
                'cancelled',
                'processing',
                'completed',
            ])->default('draft')->index();
            $table->timestamp('document_date')->index();
            $table->uuid('related_order_id')->nullable()->index();
            $table->uuid('related_movement_id')->nullable()->index();
            $table->uuid('supplier_id')->nullable()->index();
            $table->json('items')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('currency', 3)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->uuid('branch_id')->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->text('approval_comment')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            // Indexes
            $table->index(['warehouse_id', 'status']);
            $table->index(['tenant_id', 'status']);
            $table->index(['document_type', 'status']);
            $table->index('created_at');

            // Foreign keys
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
        });

        // Add comment
        DB::statement("ALTER TABLE warehouse_documents COMMENT = 'Warehouse documents for document workflow system'");
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_documents');
    }
};
