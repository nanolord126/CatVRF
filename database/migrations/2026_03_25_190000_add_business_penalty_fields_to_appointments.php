<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AddBusinessPenaltyFieldsToAppointments (Canon 2026)
 * Добавляет поля для учета штрафов бизнеса (салон/мастер) при отмене без причины.
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
                if (!Schema::hasColumn('appointments', 'business_penalty_amount')) {
                    $table->integer('business_penalty_amount')->default(0)->comment('Сумма штрафа для бизнеса в копейках');
                    $table->integer('client_compensation_bonus')->default(0)->comment('Дополнительный бонус клиенту в копейках');
                    $table->string('business_penalty_status')->nullable()->comment('pending, paid, failed, waived');
                    $table->boolean('is_unjustified_cancellation')->default(false)->index();
                    $table->timestamp('business_cancelled_at')->nullable();
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
                    'business_penalty_amount',
                    'client_compensation_bonus',
                    'business_penalty_status',
                    'is_unjustified_cancellation',
                    'business_cancelled_at',
                ]);
            });
        }
    }
};
