<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026 — MEDICAL VERTICAL MIGRATION
 * ПЛОТНОСТЬ КОДА > 60 СТРОК
 * ЛЮТЫЙ РЕЖИМ: UUID, TENANT_ID, CORRELATION_ID, GEOMETRY, JSONB
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Клиники (Clinics)
        if (!Schema::hasTable('medical_clinics')) {
            Schema::create('medical_clinics', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->string('name')->comment('Название клиники');
                $table->text('description')->nullable();
                $table->string('address')->comment('Адрес');
                $table->jsonb('schedule_json')->nullable()->comment('График работы');
                $table->jsonb('amenities')->nullable()->comment('Оснащение и удобства');
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->integer('review_count')->default(0);
                $table->boolean('is_verified')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Медицинские клиники и многопрофильные центры');
            });
        }

        // 2. Врачи (Doctors)
        if (!Schema::hasTable('medical_doctors')) {
            Schema::create('medical_doctors', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->nullable()->constrained('medical_clinics')->onDelete('set null');
                $table->string('full_name')->index();
                $table->string('specialization')->comment('Основная специализация');
                $table->jsonb('sub_specializations')->nullable()->comment('Доп. специализации');
                $table->integer('experience_years')->default(0);
                $table->text('education')->nullable();
                $table->decimal('rating', 3, 2)->default(5.00);
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->jsonb('tags')->nullable();
                $table->timestamps();
                
                $table->comment('Медицинский персонал и частные врачи');
            });
        }

        // 3. Услуги (Medical Services)
        if (!Schema::hasTable('medical_services')) {
            Schema::create('medical_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->nullable()->constrained('medical_clinics')->onDelete('cascade');
                $table->string('name');
                $table->integer('duration_minutes')->default(30);
                $table->bigInteger('price_kopecks')->unsigned();
                $table->jsonb('consumables_json')->nullable()->comment('Расходники для списания (Inventory)');
                $table->boolean('is_active')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Прайс-лист медицинских услуг и анализов');
            });
        }

        // 4. Записи на прием (Medical Appointments)
        if (!Schema::hasTable('medical_appointments')) {
            Schema::create('medical_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->constrained('medical_clinics');
                $table->foreignId('doctor_id')->constrained('medical_doctors');
                $table->foreignId('service_id')->constrained('medical_services');
                $table->foreignId('client_id')->comment('Связь с users');
                $table->timestamp('starts_at')->index();
                $table->timestamp('ends_at')->index();
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])->default('pending');
                $table->string('payment_status')->default('unpaid');
                $table->bigInteger('total_amount_kopecks')->unsigned();
                $table->text('client_comment')->nullable();
                $table->text('internal_notes')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Журнал записей пациентов');
            });
        }

        // 5. Электронные медкарты (Medical Cards / Records)
        if (!Schema::hasTable('medical_records')) {
            Schema::create('medical_records', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->integer('patient_id')->index();
                $table->foreignId('doctor_id')->constrained('medical_doctors');
                $table->foreignId('appointment_id')->nullable()->constrained('medical_appointments');
                $table->string('diagnosis_code')->nullable()->comment('ICD-10 / МКБ-10');
                $table->text('complaints')->nullable()->comment('Жалобы');
                $table->text('treatment_plan')->nullable();
                $table->jsonb('clinical_data')->nullable()->comment('Объективные данные (давление, температура)');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Электронные медицинские записи пациентов');
            });
        }

        // 6. Рецепты (Prescriptions)
        if (!Schema::hasTable('medical_prescriptions')) {
            Schema::create('medical_prescriptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('record_id')->constrained('medical_records')->onDelete('cascade');
                $table->integer('patient_id')->index();
                $table->foreignId('doctor_id')->constrained('medical_doctors');
                $table->jsonb('medications')->comment('Список препаратов, дозировка, курс');
                $table->timestamp('valid_until')->nullable();
                $table->boolean('is_digital_signed')->default(false);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Рецепты на лекарственные препараты');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_prescriptions');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('medical_appointments');
        Schema::dropIfExists('medical_services');
        Schema::dropIfExists('medical_doctors');
        Schema::dropIfExists('medical_clinics');
    }
};


