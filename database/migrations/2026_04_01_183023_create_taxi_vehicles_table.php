<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('taxi_vehicles')) {
            return;
        }

        Schema::create('taxi_vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Уникальный идентификатор транспортного средства');
            $table->string('brand')->comment('Марка');
            $table->string('model')->comment('Модель');
            $table->string('license_plate')->unique()->comment('Государственный номер');
            $table->string('class')->comment('Класс (economy, comfort, business)');
            $table->boolean('is_in_use')->default(false)->comment('Используется ли в данный момент');

            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Транспортные средства такси');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_vehicles');
    }
};
