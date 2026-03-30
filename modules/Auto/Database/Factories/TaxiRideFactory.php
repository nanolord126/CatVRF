<?php declare(strict_types=1);

namespace Modules\Auto\Database\Factories;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class TaxiRideFactory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $model = TaxiRide::class;
    
        public function definition(): array
        {
            return [
                'tenant_id'       => 1,
                'driver_id'       => $this->faker->numberBetween(1, 100),
                'passenger_id'    => $this->faker->numberBetween(1, 100),
                'vehicle_class'   => $this->faker->randomElement(['economy', 'comfort', 'business']),
                'pickup_lat'      => $this->faker->latitude(),
                'pickup_lng'      => $this->faker->longitude(),
                'dropoff_lat'     => $this->faker->latitude(),
                'dropoff_lng'     => $this->faker->longitude(),
                'distance_km'     => $this->faker->randomFloat(2, 1, 50),
                'fare_amount'     => $this->faker->numberBetween(10000, 200000),
                'surge_multiplier'=> 1.0,
                'status'          => 'pending',
                'correlation_id'  => Str::uuid()->toString(),
                'uuid'            => Str::uuid()->toString(),
            ];
        }
    
        public function accepted(): static
        {
            return $this->state(['status' => 'accepted']);
        }
    
        public function completed(): static
        {
            return $this->state(['status' => 'completed', 'completed_at' => now()]);
        }
}
