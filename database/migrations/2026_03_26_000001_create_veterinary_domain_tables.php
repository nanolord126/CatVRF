<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Veterinary Domain 2026 Comprehensive Migration
     */
    public function up(): void
    {
        // 1. Veterinary Clinics
        if (!Schema::hasTable('veterinary_clinics')) {
            Schema::create('veterinary_clinics', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('business_group_id')->nullable()->constrained('business_groups')->onDelete('cascade');
                
                $table->string('name')->comment('Название ветеринарной клиники');
                $table->string('address')->comment('Адрес клиники');
                $table->geometry('geo_point')->nullable()->comment('Гео-точка для поиска');
                $table->json('schedule_json')->nullable()->comment('Расписание работы клиники');
                
                $table->float('rating')->default(0)->comment('Рейтинг клиники');
                $table->integer('review_count')->default(0)->comment('Количество отзывов');
                $table->boolean('is_verified')->default(false)->comment('Статус верификации');
                $table->boolean('has_emergency')->default(false)->comment('Наличие скорой помощи 24/7');
                
                $table->jsonb('tags')->nullable()->comment('Теги для фильтрации и аналитики');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Ветеринарные клиники платформы CatVRF 2026');
            });
        }

        // 2. Veterinarians (Doctors)
        if (!Schema::hasTable('veterinarians')) {
            Schema::create('veterinarians', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->nullable()->constrained('veterinary_clinics')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                
                $table->string('full_name')->comment('ФИО ветеринара');
                $table->jsonb('specialization')->comment('Специализация: хирург, терапевт и т.д.');
                $table->integer('experience_years')->default(0)->comment('Опыт работы в годах');
                $table->text('bio')->nullable()->comment('Биография и заслуги');
                
                $table->float('rating')->default(0)->index();
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Ветеринарные врачи и специалисты');
            });
        }

        // 3. Pets
        if (!Schema::hasTable('pets')) {
            Schema::create('pets', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
                
                $table->string('name')->comment('Кличка питомца');
                $table->string('species')->comment('Вид (собака, кошка, игуана)');
                $table->string('breed')->nullable()->comment('Порода');
                $table->date('birth_date')->nullable()->comment('Дата рождения');
                $table->enum('gender', ['male', 'female', 'unknown'])->default('unknown');
                $table->float('weight')->nullable()->comment('Вес в кг');
                
                $table->text('medical_notes')->nullable()->comment('Критические медицинские примечания');
                $table->jsonb('vaccination_history')->nullable()->comment('История вакцинации');
                $table->string('chip_number')->nullable()->unique()->comment('Номер микрочипа');
                
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Домашние животные пользователей');
            });
        }

        // 4. Veterinary Services
        if (!Schema::hasTable('veterinary_services')) {
            Schema::create('veterinary_services', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->nullable()->constrained('veterinary_clinics')->onDelete('cascade');
                
                $table->string('name')->comment('Название услуги (Вакцинация, Стерилизация)');
                $table->text('description')->nullable();
                $table->integer('duration_minutes')->default(30)->comment('Длительность в минутах');
                $table->integer('price')->comment('Цена в копейках');
                $table->enum('category', ['therapy', 'surgery', 'vaccination', 'grooming', 'diagnostics', 'emergency'])->index();
                
                $table->jsonb('consumables_json')->nullable()->comment('Список необходимых расходников');
                $table->boolean('is_active')->default(true);
                
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Услуги ветеринарных клиник');
            });
        }

        // 5. Veterinary Appointments (Bookings)
        if (!Schema::hasTable('veterinary_appointments')) {
            Schema::create('veterinary_appointments', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->constrained('veterinary_clinics')->onDelete('cascade');
                $table->foreignId('veterinarian_id')->nullable()->constrained('veterinarians')->onDelete('cascade');
                $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
                $table->foreignId('service_id')->constrained('veterinary_services')->onDelete('cascade');
                $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
                
                $table->dateTime('appointment_at')->index()->comment('Дата и время записи');
                $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('pending')->index();
                $table->integer('final_price')->comment('Итоговая цена в копейках');
                $table->enum('payment_status', ['unpaid', 'partially_paid', 'paid', 'refunded'])->default('unpaid');
                
                $table->text('symptoms')->nullable()->comment('Жалобы при записи');
                $table->text('cancellation_reason')->nullable();
                
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->index(['tenant_id', 'status']);
                $table->comment('Записи на прием в ветеринарные клиники');
            });
        }

        // 6. Medical Records (Treatment History)
        if (!Schema::hasTable('veterinary_medical_records')) {
            Schema::create('veterinary_medical_records', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
                $table->foreignId('veterinarian_id')->constrained('veterinarians')->onDelete('cascade');
                $table->foreignId('appointment_id')->nullable()->constrained('veterinary_appointments')->onDelete('set null');
                
                $table->string('diagnosis')->comment('Поставленный диагноз');
                $table->text('treatment_plan')->comment('План лечения');
                $table->text('prescribed_medication')->nullable()->comment('Назначенные препараты');
                $table->jsonb('lab_results')->nullable()->comment('Результаты анализов');
                
                $table->dateTime('next_visit_at')->nullable()->comment('Дата следующего визита (рекомендация)');
                $table->boolean('is_confidential')->default(true)->comment('Признак конфиденциальности данных');
                
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Электронные медицинские карты питомцев');
            });
        }

        // 7. Veterinary Consumables (Inventory)
        if (!Schema::hasTable('veterinary_consumables')) {
            Schema::create('veterinary_consumables', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('clinic_id')->constrained('veterinary_clinics')->onDelete('cascade');
                
                $table->string('name')->comment('Название расходника (Шприц, Вакцина X, Бинт)');
                $table->string('sku')->index();
                $table->integer('current_stock')->default(0);
                $table->integer('min_stock_threshold')->default(10);
                $table->integer('price_per_unit')->comment('Себестоимость/цена за единицу');
                
                $table->enum('type', ['medicine', 'tool', 'grooming_supply', 'supplements'])->default('medicine');
                $table->date('expiration_date')->nullable()->comment('Срок годности');
                
                $table->jsonb('tags')->nullable();
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();

                $table->comment('Расходные материалы и медикаменты ветеринарных клиник');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veterinary_consumables');
        Schema::dropIfExists('veterinary_medical_records');
        Schema::dropIfExists('veterinary_appointments');
        Schema::dropIfExists('veterinary_services');
        Schema::dropIfExists('pets');
        Schema::dropIfExists('veterinarians');
        Schema::dropIfExists('veterinary_clinics');
    }
};


