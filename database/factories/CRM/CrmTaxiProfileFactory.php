<?php

declare(strict_types=1);

namespace Database\Factories\CRM;

use App\Domains\CRM\Models\CrmTaxiProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика CrmTaxiProfile — taxi-профиль CRM-клиента.
 * Канон CatVRF 2026.
 */
final class CrmTaxiProfileFactory extends Factory
{
    protected $model = CrmTaxiProfile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crm_client_id' => null,
            'tenant_id' => 1,
            'correlation_id' => $this->faker->uuid(),
            'frequent_routes' => [],
            'home_address' => ['city' => $this->faker->city(), 'street' => $this->faker->streetAddress()],
            'work_address' => ['city' => $this->faker->city(), 'street' => $this->faker->streetAddress()],
            'saved_addresses' => [],
            'preferred_car_class' => $this->faker->randomElement(['economy', 'comfort', 'business', 'premium']),
            'preferred_payment' => $this->faker->randomElement(['card', 'cash', 'corporate']),
            'is_corporate' => $this->faker->boolean(20),
            'corporate_account_id' => null,
            'monthly_ride_budget' => $this->faker->optional(0.3)->randomFloat(2, 5000, 50000),
            'total_rides' => $this->faker->numberBetween(0, 500),
            'total_spent_rides' => $this->faker->randomFloat(2, 0, 200000),
            'avg_rating_given' => $this->faker->randomFloat(2, 3.0, 5.0),
            'preferred_drivers' => [],
            'ride_time_patterns' => [],
            'needs_child_seat' => $this->faker->boolean(15),
            'needs_pet_friendly' => $this->faker->boolean(10),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }
}
