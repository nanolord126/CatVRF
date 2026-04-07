<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AddForceMajeureFieldsToAppointments (Canon 2026)
 * Добавляет поля для учета форс-мажорных обстоятельств и компенсаций.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'is_force_majeure')) {
                    $table->boolean('is_force_majeure')->default(false)->index();
                    $table->string('force_majeure_type')->nullable()->index();
                    $table->string('force_majeure_party')->nullable()->index();
                    $table->jsonb('force_majeure_proof')->nullable();
                    $table->integer('compensation_amount')->default(0)->comment('Сумма компенсации в копейках');
                    $table->string('compensation_type')->nullable()->comment('Тип компенсации: full_refund, partial, bonus, provider_compensation');
                    $table->timestamp('force_majeure_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn([
                    'is_force_majeure',
                    'force_majeure_type',
                    'force_majeure_party',
                    'force_majeure_proof',
                    'compensation_amount',
                    'compensation_type',
                    'force_majeure_at',
                ]);
            });
        }
    }
};


