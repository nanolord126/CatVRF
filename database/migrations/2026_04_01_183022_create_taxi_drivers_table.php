<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('taxi_drivers')) {
            return;
        }

        Schema::create('taxi_drivers', function (Blueprint $table) {
            $table->uuid('id')->primary()->comment('Уникальный идентификатор водителя');
            $table->string('name')->comment('Имя водителя');
            $table->string('license_number')->unique()->comment('Номер водительского удостоверения');
            $table->boolean('is_available')->default(true)->comment('Доступен ли водитель для заказов');
            
            $table->foreignUuid('vehicle_id')->nullable()->constrained('taxi_vehicles')->onDelete('set null');
            $table->foreignUuid('fleet_id')->nullable()->constrained('taxi_fleets')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            
            $table->string('correlation_id')->nullable()->index();
            $table->jsonb('tags')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Водители такси');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_drivers');
    }
};
