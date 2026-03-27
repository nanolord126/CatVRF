<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Миграция для вертикали PsychologicalServices 2026.
     * Включает поддержку клиник, психологов, сессий и строгого логирования конфиденциальности.
     */
    public function up(): void
    {
        // 1. Психологические клиники (Центры терапии)
        if (!Schema::hasTable('psy_clinics')) {
            Schema::create('psy_clinics', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->string('name')->comment('Название центра психологической помощи');
                $table->text('description')->nullable();
                $table->jsonb('metadata')->nullable()->comment('Адрес, контакты, лицензии, оборудование');
                $table->decimal('rating', 3, 2)->default(0.00);
                $table->jsonb('tags')->nullable()->comment('Аналитические теги: #КПТ, #Гештальт, #Детский');
                $table->string('correlation_id')->nullable()->index();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'is_active']);
                $table->comment('Таблица психологических центров и клиник');
            });
        }

        // 2. Психологи / Терапевты / Коучи
        if (!Schema::hasTable('psychologists')) {
            Schema::create('psychologists', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->comment('Связь с системным пользователем');
                $table->unsignedBigInteger('clinic_id')->nullable()->index();
                $table->string('full_name');
                $table->string('specialization')->comment('Основное направление (напр. Клинический психолог)');
                $table->jsonb('therapy_types')->comment('Массив типов терапии: [CBT, Gestalt, psychoanalysis]');
                $table->integer('experience_years')->default(0);
                $table->text('biography')->nullable();
                $table->jsonb('education')->nullable()->comment('Дипломы, сертификаты, супервизии');
                $table->integer('base_price_per_hour')->comment('Базовая цена сессии в копейках');
                $table->jsonb('metadata')->nullable()->comment('Работает онлайн/оффлайн, языки, часовой пояс');
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->boolean('is_available')->default(true);
                $table->timestamps();

                $table->foreign('clinic_id')->references('id')->on('psy_clinics')->onDelete('set null');
                $table->index(['tenant_id', 'specialization']);
                $table->comment('Таблица практикующих психологов и терапевтов');
            });
        }

        // 3. Услуги (Типы консультаций)
        if (!Schema::hasTable('psy_services')) {
            Schema::create('psy_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('psychologist_id')->index();
                $table->string('name')->comment('Название услуги (Индивидуальная, Семейная, Групповая)');
                $table->integer('duration_minutes')->default(50);
                $table->integer('price')->comment('Цена в копейках');
                $table->enum('delivery_type', ['online', 'offline', 'hybrid'])->default('online');
                $table->text('description')->nullable();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('psychologist_id')->references('id')->on('psychologists')->onDelete('cascade');
                $table->comment('Каталог услуг психологов');
            });
        }

        // 4. Бронирования (Записи на сессии)
        if (!Schema::hasTable('psy_bookings')) {
            Schema::create('psy_bookings', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('psychologist_id')->index();
                $table->unsignedBigInteger('service_id')->index();
                $table->timestamp('scheduled_at')->index();
                $table->integer('price_at_booking')->comment('Цена на момент записи');
                $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled', 'completed'])->default('pending');
                $table->string('payment_id')->nullable()->comment('Связь с payment_transactions');
                $table->text('client_notes')->nullable()->comment('Жалобы пациента (только для врача)');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->index(['tenant_id', 'status', 'scheduled_at']);
                $table->comment('Записи клиентов на психологические консультации');
            });
        }

        // 5. Терапевтические сессии (Медицинская карта / Лог работы)
        if (!Schema::hasTable('psy_sessions')) {
            Schema::create('psy_sessions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('booking_id')->unique()->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->text('therapist_notes')->nullable()->comment('Конфиденциальные заметки терапевта');
                $table->text('homework')->nullable()->comment('Домашнее задание для клиента');
                $table->jsonb('emotional_state')->nullable()->comment('AI-скоринг состояния: [stress_level, mood]');
                $table->string('video_link')->nullable()->comment('Ссылка на защищенную комнату сессии');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('booking_id')->references('id')->on('psy_bookings')->onDelete('cascade');
                $table->comment('Результаты и протоколы проведенных сессий');
            });
        }

        // 6. Отзывы (Анонимизированные)
        if (!Schema::hasTable('psy_reviews')) {
            Schema::create('psy_reviews', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('psychologist_id')->index();
                $table->integer('rating')->default(5);
                $table->text('comment')->nullable();
                $table->boolean('is_public')->default(true);
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->foreign('psychologist_id')->references('id')->on('psychologists')->onDelete('cascade');
                $table->comment('Обратная связь о работе психолога');
            });
        }

        // 7. Журнал конфиденциальности (Audit Log для ФЗ-152)
        if (!Schema::hasTable('psy_confidentiality_logs')) {
            Schema::create('psy_confidentiality_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->index();
                $table->unsignedBigInteger('user_id')->comment('Кто получил доступ к данным');
                $table->unsignedBigInteger('session_id')->nullable()->comment('К какой сессии');
                $table->string('action')->comment('view_notes, edit_diagnosis, download_report');
                $table->string('ip_address')->nullable();
                $table->text('reason')->comment('Обоснование доступа');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();

                $table->comment('Строгий аудит доступа к чувствительным данным пациентов');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('psy_confidentiality_logs');
        Schema::dropIfExists('psy_reviews');
        Schema::dropIfExists('psy_sessions');
        Schema::dropIfExists('psy_bookings');
        Schema::dropIfExists('psy_services');
        Schema::dropIfExists('psychologists');
        Schema::dropIfExists('psy_clinics');
    }
};
