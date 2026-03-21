<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medical_appointments')) return;

        Schema::create('medical_appointments', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->foreignId('clinic_id')->comment('ID клиники');
            $table->foreignId('doctor_id')->comment('ID врача');
            $table->string('patient_name')->comment('Фамилия пациента');
            $table->string('patient_phone')->comment('Телефон пациента');
            $table->dateTime('appointment_date')->index()->comment('Дата прёма');
            $table->integer('duration_minutes')->default(30)->comment('Продолжительность (мин)');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending')->comment('Статус');
            $table->integer('price')->comment('Цена (коп)');
            $table->text('notes')->nullable()->comment('Заметки');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_appointments');
    }
};
