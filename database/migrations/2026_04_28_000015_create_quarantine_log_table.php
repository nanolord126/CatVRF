<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Quarantine Log Table
 * 
 * Таблица для журнала карантина (quarantine workflow)
 * в соответствии с ФЗ-61 compliance
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quarantine_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('batch_id')->index()->comment('ID партии');
            $table->enum('action', ['placed_in_quarantine', 'released_from_quarantine', 'inspection_started', 'inspection_completed'])->index();
            $table->string('result')->nullable()->comment('Результат (approved/rejected)');
            $table->text('reason')->nullable()->comment('Причина помещения в карантин');
            $table->text('notes')->nullable()->comment('Примечания инспекции');
            $table->string('original_status')->nullable()->comment('Исходный статус');
            $table->foreignId('performed_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('performed_at')->index()->comment('Время действия');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['batch_id', 'performed_at']);
            $table->index(['action', 'performed_at']);
        });

        Schema::create('serial_number_blacklist', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('serial_number')->unique()->index()->comment('Серийный номер в черном списке');
            $table->string('reason')->comment('Причина добавления в черный список');
            $table->foreignId('added_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true)->index()->comment('Активен ли запрет');
            $table->timestamp('added_at')->useCurrent();
            $table->softDeletes();

            $table->index(['serial_number', 'is_active']);
        });

        DB::statement("ALTER TABLE quarantine_log COMMENT = 'Quarantine workflow log per FZ-61'");
        DB::statement("ALTER TABLE serial_number_blacklist COMMENT = 'Blacklisted serial numbers (recalled products)'");
    }

    public function down(): void
    {
        Schema::dropIfExists('serial_number_blacklist');
        Schema::dropIfExists('quarantine_log');
    }
};
