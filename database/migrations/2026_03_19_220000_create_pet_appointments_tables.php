<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pet_appointments')) return;

        Schema::create('pet_appointments', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->foreignId('pet_clinic_id')->comment('ID ветклиники');
            $table->foreignId('vet_id')->comment('ID ветеринара');
            $table->string('pet_name')->comment('Имя питомца');
            $table->enum('pet_type', ['dog', 'cat', 'bird', 'rabbit', 'hamster'])->comment('Вид животного');
            $table->string('owner_phone')->comment('Телефон владельца');
            $table->dateTime('appointment_date')->index()->comment('Дата приёма');
            $table->enum('service_type', ['grooming', 'vaccination', 'checkup', 'treatment'])->comment('Тип услуги');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending')->comment('Статус');
            $table->integer('price')->comment('Цена (коп)');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_appointments');
    }
};
