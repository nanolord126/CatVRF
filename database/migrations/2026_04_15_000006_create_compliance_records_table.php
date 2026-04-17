<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица compliance_records — журнал соответствия нормативным требованиям.
 *
 * Хранит события: GDPR-запросы, проверки ФЗ-152, маркировка MDLP/Mercury,
 * ежегодная анонимизация (AnnualAnonymizationJob), права на забвение.
 * ComplianceRequirementService + MdlpService + MercuryService пишут сюда.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compliance_records', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('business_group_id')->nullable()->index();

            // Тип события
            $table->enum('record_type', [
                'gdpr_request',         // запрос на экспорт данных
                'gdpr_erasure',         // право на забвение
                'fz152_check',          // проверка ФЗ-152
                'annual_anonymization', // ежегодная анонимизация
                'mdlp_marking',         // маркировка лекарств (МДЛП)
                'mercury_check',        // ветеринарный контроль (Меркурий)
                'age_verification',     // верификация возраста
                'kyc_check',            // Know Your Customer (B2B)
                'aml_check',            // Anti-Money Laundering
                'data_retention',       // политика хранения данных
                'consent_update',       // обновление согласия
                'other',
            ])->index();

            // Субъект (пользователь или сущность)
            $table->string('subject_type')->nullable()->comment('User, BusinessGroup, Product...');
            $table->unsignedBigInteger('subject_id')->nullable()->index();

            // Статус
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'requires_manual_review'])
                ->default('pending')->index();

            // Данные события
            $table->json('request_data')->nullable()->comment('Входящий запрос или параметры проверки');
            $table->json('result_data')->nullable()->comment('Результат обработки');
            $table->text('notes')->nullable()->comment('Заметки оператора при ручной проверке');

            // Дедлайн (GDPR требует 30 дней, ФЗ-152 — 10 дней)
            $table->timestamp('deadline_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();

            // Инициатор
            $table->unsignedBigInteger('initiated_by_user_id')->nullable()
                ->comment('NULL = автоматическая проверка');
            $table->string('initiated_by_system')->nullable()->comment('AnnualAnonymizationJob, MdlpService...');

            $table->json('tags')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['tenant_id', 'record_type', 'status']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['deadline_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compliance_records');
    }
};
