<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Temperature Alerts Table
 * 
 * Таблица для хранения алертов о нарушениях температурного режима
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temperature_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
            $table->decimal('temperature', 5, 2)->comment('Температура при нарушении');
            $table->decimal('min_allowed', 5, 2)->comment('Минимальная допустимая температура');
            $table->decimal('max_allowed', 5, 2)->comment('Максимальная допустимая температура');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->index()->comment('Серьезность нарушения');
            $table->timestamp('resolved_at')->nullable()->comment('Время устранения');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();

            // Indexes
            $table->index(['zone_id', 'severity']);
            $table->index(['zone_id', 'created_at']);
            $table->index(['severity', 'resolved_at']);
        });

        DB::statement("ALTER TABLE temperature_alerts COMMENT = 'Temperature violation alerts'");
    }

    public function down(): void
    {
        Schema::dropIfExists('temperature_alerts');
    }
};
