<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * КАНОН 2026: МИГРАЦИЯ ВЕРТИКАЛИ MEDICAL.
 * Слой 1: Таблицы и миграции.
 * Название: [2026_03_26_300001_create_medical_tables.php]
 */
return new class extends Migration
{
    /**
     * Создание таблиц с полным набором полей по канону 2026.
     * Обязательно: tenant_id, uuid, correlation_id, tags, comments.
     */
    public function up(): void
    {
        // 1. Клиники (Clinics)
        if (!Schema::hasTable('medical_clinics')) {
            Schema::create('medical_clinics', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('set null');
                
                $table->string('name')->index();
                $table->string('license_number')->unique()->comment('Лицензия на мед. деятельность (обязательно по ФЗ-152)');
                $table->string('address');
                $table->jsonb('geo_point')->nullable()->comment('Координаты клиники [lat, lon]');
                
                $table->string('status', 32)->default('active')->index()->comment('active, suspended, verification');
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('review_count')->default(0);
                
                $table->jsonb('contact_info')->nullable()->comment('phone, email, website');
                $table->jsonb('working_hours')->nullable()->comment('Пн-Вс график работы');
                $table->jsonb('metadata')->nullable();
                $table->jsonb('tags')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Таблица клиник медицинской вертикали (Human Medicine)');
            });
        }

        // 2. Врачи (Doctors)
        if (!Schema::hasTable('medical_doctors')) {
            Schema::create('medical_doctors', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->constrained('medical_clinics');
                $table->foreignId('user_id')->nullable()->constrained('users');
                
                $table->string('full_name')->index();
                $table->jsonb('specialization')->comment('Массив специализаций: Терапевт, Хирург и т.д.');
                $table->integer('experience_years')->default(0);
                $table->string('degree')->nullable()->comment('Ученая степень: к.м.н, д.м.н');
                
                $table->string('status', 32)->default('active')->index()->comment('active, vacation, sick_leave, retired');
                $table->decimal('rating', 3, 2)->default(0);
                $table->integer('consultation_price')->default(0)->comment('Базовая цена приема (в копейках)');
                
                $table->jsonb('schedule_config')->nullable()->comment('Конфигурация слотов записи');
                $table->jsonb('metadata')->nullable();
                $table->jsonb('tags')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Таблица врачей с привязкой к клиникам и пользователям системы');
            });
        }

        // 3. Услуги (Medical Services)
        if (!Schema::hasTable('medical_services')) {
            Schema::create('medical_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->constrained('medical_clinics');
                
                $table->string('name')->index();
                $table->text('description')->nullable();
                $table->string('category', 64)->index();
                $table->integer('price')->default(0)->comment('Цена услуги в копейках');
                $table->integer('duration_minutes')->default(30);
                
                $table->boolean('requires_prepayment')->default(false)->comment('Требуется ли предоплата для записи');
                $table->boolean('is_complex')->default(false)->comment('Сложная услуга (требует подготовки)');
                
                $table->jsonb('consumables_needed')->nullable()->comment('Список необходимых расходников (тип, кол-во)');
                $table->jsonb('metadata')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Каталог медицинских услуг: приемы, анализы, процедуры');
            });
        }

        // 4. Записки/Приемы (Appointments)
        if (!Schema::hasTable('medical_appointments')) {
            Schema::create('medical_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->constrained('medical_clinics');
                $table->foreignId('doctor_id')->constrained('medical_doctors');
                $table->foreignId('service_id')->constrained('medical_services');
                $table->foreignId('patient_id')->constrained('users')->comment('Пациент как User системы');
                
                $table->dateTime('appointment_at')->index();
                $table->integer('duration_minutes')->default(30);
                
                $table->string('status', 32)->default('pending')->index()->comment('pending, confirmed, in_progress, completed, cancelled, no_show');
                $table->integer('price')->default(0)->comment('Фактическая цена на момент записи');
                $table->string('payment_status', 32)->default('unpaid')->index()->comment('unpaid, partial, paid, refunded');
                
                $table->text('patient_complaint')->nullable()->comment('Жалоба пациента при записи');
                $table->jsonb('metadata')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['tenant_id', 'status', 'appointment_at']);
                $table->comment('Записи пациентов на прием к врачам');
            });
        }

        // 5. Медицинские карты (Medical Records / Clinical Notes)
        if (!Schema::hasTable('medical_records')) {
            Schema::create('medical_records', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('appointment_id')->constrained('medical_appointments');
                $table->foreignId('patient_id')->constrained('users');
                $table->foreignId('doctor_id')->constrained('medical_doctors');
                
                $table->text('anamnesis')->nullable()->comment('Анамнез заболевания');
                $table->text('objective_status')->nullable()->comment('Объективный статус');
                $table->text('diagnosis')->nullable()->index()->comment('Диагноз (МКБ-10 поиск)');
                $table->text('treatment_plan')->nullable()->comment('План лечения');
                $table->jsonb('prescriptions')->nullable()->comment('Список назначений и лекарств');
                
                $table->boolean('is_confidential')->default(true)->comment('Признак врачебной тайны ФЗ-152');
                $table->jsonb('attachments')->nullable()->comment('Ссылки на файлы анализов/снимков');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Записи в медицинских картах по итогам приемов');
            });
        }

        // 6. Расходники (Medical Consumables)
        if (!Schema::hasTable('medical_consumables')) {
            Schema::create('medical_consumables', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->constrained('medical_clinics');
                
                $table->string('name')->index();
                $table->string('sku')->nullable()->index();
                $table->integer('current_stock')->default(0);
                $table->integer('min_threshold')->default(10);
                $table->string('unit', 16)->default('pcs')->comment('pcs, ml, gram, pack');
                
                $table->integer('price_per_item')->default(0);
                $table->jsonb('metadata')->nullable();
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Учет медицинских расходников: шприцы, маски, медикаменты');
            });
        }

        // 7. Отзывы (Reviews)
        if (!Schema::hasTable('medical_reviews')) {
            Schema::create('medical_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('appointment_id')->unique()->constrained('medical_appointments');
                $table->foreignId('patient_id')->constrained('users');
                $table->foreignId('doctor_id')->constrained('medical_doctors');
                
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->boolean('is_anonymous')->default(false);
                $table->boolean('is_verified')->default(true);
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Отзывы пациентов о врачах и клиниках после приемов');
            });
        }
    }

    /**
     * Откат миграции. Down() — только dropIfExists по канону.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_reviews');
        Schema::dropIfExists('medical_consumables');
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('medical_appointments');
        Schema::dropIfExists('medical_services');
        Schema::dropIfExists('medical_doctors');
        Schema::dropIfExists('medical_clinics');
    }
};


