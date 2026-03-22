<?php declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apartments')) return;

        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('owner_id')->index();
            $table->uuid('uuid')->unique()->index();
            $table->string('name');
            $table->string('address');
            $table->point('geo_point')->nullable();
            $table->integer('rooms');
            $table->float('area_sqm');
            $table->integer('floor');
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->decimal('price_per_night', 10, 2);
            $table->json('available_dates')->nullable();
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('correlation_id')->nullable()->index();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->comment('Квартиры посуточно');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
