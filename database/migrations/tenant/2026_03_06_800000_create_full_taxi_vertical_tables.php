<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Stub: full taxi vertical tables handled in root migrations
    }

    public function down(): void
    {
        // Intentionally left empty
    }
};
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('license_plate')->unique();
            $table->enum('class', ['economy', 'comfort', 'business', 'vip', 'cargo'])->default('economy');
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable(); // Wi-Fi, Детское кресло, Животные
            $table->timestamps();
        });

        // 2. Водители (Связь с HR Модулем)
        Schema::create('taxi_drivers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Связь с пользователем тенанта
            $table->unsignedBigInteger('current_vehicle_id')->nullable();
            $table->enum('status', ['offline', 'available', 'on_ride', 'resting'])->default('offline');
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->text('last_location')->nullable(); // Текущие координаты для Dispatcher
            $table->timestamp('last_online_at')->nullable();
            $table->timestamps();

            $table->foreign('current_vehicle_id')->references('id')->on('taxi_vehicles')->onDelete('set null');
        });

        // 3. Поездки (Ride Hailing Core)
        Schema::create('taxi_rides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->unsignedBigInteger('vehicle_id')->nullable();
            
            // Гео-маршрут
            $table->string('pickup_address');
            $table->text('pickup_coords');
            $table->string('destination_address');
            $table->text('destination_coords');
            
            // Финансы (Интеграция с Wallet/Pricing)
            $table->decimal('estimated_price', 12, 2);
            $table->decimal('final_price', 12, 2)->nullable();
            $table->decimal('surge_multiplier', 4, 2)->default(1.00);
            
            $table->enum('status', ['searching', 'accepted', 'arrived', 'picked_up', 'completed', 'cancelled'])->default('searching');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamps();
        });

        // 4. Смены (Shift Management)
        Schema::create('taxi_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('driver_id');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->decimal('total_earnings', 12, 2)->default(0);
            $table->integer('total_rides')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxi_shifts');
        Schema::dropIfExists('taxi_rides');
        Schema::dropIfExists('taxi_drivers');
        Schema::dropIfExists('taxi_vehicles');
    }
};
