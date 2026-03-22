<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apartment_reviews')) return;

        Schema::create('apartment_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('apartment_id')->index();
            $table->unsignedBigInteger('booking_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->json('images')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Отзывы о квартирах');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartment_reviews');
    }
};
