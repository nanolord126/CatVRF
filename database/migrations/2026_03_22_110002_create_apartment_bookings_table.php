<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apartment_bookings')) return;

        Schema::create('apartment_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('apartment_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('inn')->nullable()->index();
            $table->unsignedBigInteger('business_card_id')->nullable();
            $table->uuid('uuid')->unique()->index();
            $table->date('check_in');
            $table->date('check_out');
            $table->integer('guests_count');
            $table->decimal('total_price', 10, 2);
            $table->decimal('deposit_held', 10, 2)->default(0);
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled']);
            $table->enum('payment_status', ['pending', 'paid', 'refunded']);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Бронирования квартир');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_bookings');
    }
};
