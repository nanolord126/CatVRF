<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Миграция для полной унификации модели Appointment (Вертикаль 2026).
     * Добавление полей для всех 12 типов событий.
     */
    public function up(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                // Тип события и его политики
                if (!Schema::hasColumn('appointments', 'appointment_type')) {
                    $table->string('appointment_type')->default('standard')->index()->after('id');
                    $table->string('cancellation_policy')->default('standard')->after('appointment_type');
                    $table->string('reschedule_policy')->default('once_free_24h')->after('cancellation_policy');
                }

                // Групповые и Специальные флаги
                if (!Schema::hasColumn('appointments', 'is_group')) {
                    $table->boolean('is_group')->default(false)->after('is_photo_session');
                    $table->integer('group_size')->nullable()->after('is_group');
                    
                    $table->boolean('is_kids_party')->default(false)->after('group_size');
                    $table->integer('kids_count')->nullable()->after('is_kids_party');
                    
                    $table->boolean('is_corporate_event')->default(false)->after('kids_count');
                    $table->unsignedBigInteger('corporate_client_id')->nullable()->index()->after('is_corporate_event');
                    
                    $table->boolean('is_luxury_service')->default(false)->after('corporate_client_id');
                    $table->boolean('is_ai_constructed')->default(false)->after('is_luxury_service');
                    
                    $table->string('location_type')->default('onsite')->after('is_ai_constructed'); // onsite/outdoor
                    $table->string('outdoor_address')->nullable()->after('location_type');
                }

                // Форс-мажор и отмены (Canon 2026 ЛЮТЫЙ РЕЖИМ 13.0)
                if (!Schema::hasColumn('appointments', 'is_force_majeure')) {
                    $table->boolean('is_force_majeure')->default(false)->index()->after('outdoor_address');
                    $table->string('force_majeure_type')->nullable()->after('is_force_majeure');
                    $table->jsonb('force_majeure_proof')->nullable()->after('force_majeure_type');
                    $table->string('cancelled_by')->nullable()->after('force_majeure_proof'); // client / provider / system
                    $table->integer('compensation_amount')->default(0)->after('cancelled_by'); // в копейках
                }

                // Связи для абонементов и сертификатов
                if (!Schema::hasColumn('appointments', 'subscription_id')) {
                    $table->unsignedBigInteger('subscription_id')->nullable()->index()->after('outdoor_address');
                    $table->unsignedBigInteger('gift_certificate_id')->nullable()->index()->after('subscription_id');
                }
                
                $table->comment('Унифицированная таблица бронирований для всех вертикалей (2026)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn([
                    'appointment_type', 'cancellation_policy', 'reschedule_policy',
                    'is_group', 'group_size', 'is_kids_party', 'kids_count',
                    'is_corporate_event', 'corporate_client_id', 'is_luxury_service',
                    'is_ai_constructed', 'location_type', 'outdoor_address',
                    'subscription_id', 'gift_certificate_id'
                ]);
            });
        }
    }
};


