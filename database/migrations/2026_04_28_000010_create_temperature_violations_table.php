<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Temperature Violations Table
 * 
 * Таблица для логирования нарушений температурного режима
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temperature_violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->decimal('current_temperature', 5, 2)->comment('Температура при нарушении');
            $table->decimal('min_allowed', 5, 2)->comment('Минимальная допустимая температура');
            $table->decimal('max_allowed', 5, 2)->comment('Максимальная допустимая температура');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->index()->comment('Серьезность нарушения');
            $table->timestamp('resolved_at')->nullable()->comment('Время устранения');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // Indexes
            $table->index(['product_id', 'created_at']);
            $table->index(['warehouse_id', 'severity']);
            $table->index(['severity', 'resolved_at']);
        });

        DB::statement("ALTER TABLE temperature_violations COMMENT = 'Temperature violation log for audit trail'");
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_violations');
    }
};
