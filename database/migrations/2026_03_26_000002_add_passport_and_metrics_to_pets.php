<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for Pet Passport, Vaccinations, Pedigree and Metrics.
     * 2026 Production Ready Standards.
     */
    public function up(): void
    {
        // 1. Таблица Прививок
        if (!Schema::hasTable('pet_vaccinations')) {
            Schema::create('pet_vaccinations', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
                $table->foreignId('veterinarian_id')->nullable()->constrained('veterinarians')->onDelete('set null');
                
                $table->string('vaccine_name')->comment('Название вакцины (например, Nobivac)');
                $table->string('serial_number')->nullable()->comment('Серийный номер/партия');
                $table->date('vaccinated_at')->index()->comment('Дата вакцинации');
                $table->date('expires_at')->index()->comment('Дата следующей ревакцинации');
                
                $table->string('certificate_url')->nullable()->comment('Скан страницы паспорта с печатью');
                $table->jsonb('metadata')->nullable()->comment('Дополнительные данные (температура, реакция)');
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
                
                $table->comment('Журнал прививок питомца (Ветеринарный паспорт)');
            });
        }

        // 2. Таблица Родословной (Pedigree)
        if (!Schema::hasTable('pet_pedigrees')) {
            Schema::create('pet_pedigrees', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('pet_id')->unique()->constrained('pets')->onDelete('cascade');
                
                $table->string('registration_number')->unique()->comment('Номер родословной (РКФ/WCF)');
                $table->string('breed_club')->nullable()->comment('Клуб/Питомник регистрации');
                
                $table->string('father_name')->nullable();
                $table->string('father_reg_number')->nullable();
                $table->string('mother_name')->nullable();
                $table->string('mother_reg_number')->nullable();
                
                $table->jsonb('ancestors_tree')->nullable()->comment('JSON дерево предков до 4 колена');
                $table->string('document_url')->nullable()->comment('Скан документа родословной');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->comment('Данные о происхождении и родословной животного');
            });
        }

        // 3. Таблица Метрик (Вес, Рост, Температура, Активность)
        if (!Schema::hasTable('pet_metrics')) {
            Schema::create('pet_metrics', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique()->index();
                $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignId('pet_id')->constrained('pets')->onDelete('cascade');
                
                $table->string('metric_type')->index()->comment('weight, height, temperature, pulse, activity_level');
                $table->float('value')->comment('Значение метрики');
                $table->string('unit')->comment('Единица измерения (kg, cm, celsius, bpm)');
                
                $table->timestamp('measured_at')->index();
                $table->string('source')->default('manual')->comment('manual, wearable_device, clinic_visit');
                
                $table->string('correlation_id')->nullable()->index();
                $table->timestamps();
                
                $table->index(['pet_id', 'metric_type', 'measured_at']);
                $table->comment('История изменений биометрических параметров питомца');
            });
        }

        // 4. Добавление полей в таблицу Pets (если их нет)
        Schema::table('pets', function (Blueprint $table) {
            if (!Schema::hasColumn('pets', 'chip_number')) {
                $table->string('chip_number')->nullable()->unique()->after('breed');
                $table->date('chip_installed_at')->nullable()->after('chip_number');
            }
            if (!Schema::hasColumn('pets', 'passport_number')) {
                $table->string('passport_number')->nullable()->unique()->after('chip_installed_at');
            }
            if (!Schema::hasColumn('pets', 'is_neutered')) {
                $table->boolean('is_neutered')->default(false)->after('passport_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pet_metrics');
        Schema::dropIfExists('pet_pedigrees');
        Schema::dropIfExists('pet_vaccinations');
        
        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn(['chip_number', 'chip_installed_at', 'passport_number', 'is_neutered']);
        });
    }
};
