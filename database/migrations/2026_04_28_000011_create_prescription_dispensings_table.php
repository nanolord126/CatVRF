<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Prescription Dispensings Table
 * 
 * Таблица для логирования отпуска рецептурных препаратов
 * в соответствии с ФЗ-323
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_dispensings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('quantity')->comment('Количество отпущенного препарата');
            $table->foreignId('dispensed_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->timestamp('dispensed_at')->index()->comment('Время отпуска');
            $table->text('notes')->nullable()->comment('Примечания');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // Indexes
            $table->index(['prescription_id', 'product_id']);
            $table->index(['product_id', 'dispensed_at']);
            $table->index(['dispensed_by', 'dispensed_at']);
            $table->index(['warehouse_id', 'dispensed_at']);
        });

        DB::statement("ALTER TABLE prescription_dispensings COMMENT = 'Prescription drug dispensing log per FZ-323'");
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_dispensings');
    }
};
