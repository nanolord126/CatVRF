<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Cold Chain Readings Table
 * 
 * Таблица для хранения показаний температурного режима
 * в соответствии с ФЗ-323 (об основах охраны здоровья)
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cold_chain_readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained('warehouse_zones')->onDelete('set null');
            $table->decimal('temperature', 5, 2)->comment('Температура в °C');
            $table->decimal('humidity', 5, 2)->nullable()->comment('Влажность в %');
            $table->string('sensor_id')->nullable()->index()->comment('ID датчика');
            $table->timestamp('recorded_at')->index()->comment('Время измерения');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // Indexes
            $table->index(['warehouse_id', 'recorded_at']);
            $table->index(['zone_id', 'recorded_at']);
            $table->index(['sensor_id', 'recorded_at']);
        });

        DB::statement("ALTER TABLE cold_chain_readings COMMENT = 'Cold chain temperature readings per FZ-323'");
    }

    public function down(): void
    {
        Schema::dropIfExists('cold_chain_readings');
    }
};
