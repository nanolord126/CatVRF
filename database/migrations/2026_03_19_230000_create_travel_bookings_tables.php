<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('travel_bookings')) return;

        Schema::create('travel_bookings', function (Blueprint $table) {
            $table->id()->comment('Идентификатор');
            $table->uuid()->unique()->comment('UUID');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade')->comment('ID тенанта');
            $table->foreignId('business_group_id')->nullable()->constrained()->onDelete('cascade')->comment('ID филиала');
            $table->string('correlation_id')->nullable()->index()->comment('ID корреляции');
            $table->foreignId('tour_id')->comment('ID тура');
            $table->string('traveler_name')->comment('Имя путешественника');
            $table->string('traveler_email')->comment('Email');
            $table->string('traveler_phone')->comment('Телефон');
            $table->dateTime('booking_date')->comment('Дата бронирования');
            $table->dateTime('departure_date')->index()->comment('Дата вылета');
            $table->integer('participants')->comment('Количество участников');
            $table->integer('total_price')->comment('Общая цена (коп)');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('confirmed')->comment('Статус');
            $table->enum('payment_status', ['pending', 'paid', 'refunded'])->default('paid')->comment('Статус оплаты');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_bookings');
    }
};
