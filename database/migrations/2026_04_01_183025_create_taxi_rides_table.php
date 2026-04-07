<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('taxi_rides')) {
            return;
        }

        Schema::create('taxi_rides', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Уникальный идентификатор поездки');
            
            $table->foreignUuid('driver_id')->nullable()->constrained('taxi_drivers')->onDelete('set null');
            $table->foreignUuid('vehicle_id')->nullable()->constrained('taxi_vehicles')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('status')->comment('Статус поездки (requested, accepted, started, finished, cancelled)');
            $table->decimal('price', 10, 2)->comment('Стоимость поездки');
            
            $table->point('start_point')->comment('Начальная точка');
            $table->point('end_point')->comment('Конечная точка');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->comment('Поездки на такси');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_rides');
    }
};
